<?php

namespace Tests\Feature;

use App\Enums\AccountStatus;
use App\Enums\OtpPurpose;
use App\Enums\UserRole;
use App\Mail\ApprovalRequestedMail;
use App\Mail\OtpCodeMail;
use App\Models\EmailOtpCode;
use App\Models\PhoneOtpCode;
use App\Models\Setting;
use App\Models\User;
use App\Services\EmailOtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class MembershipSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::updateOrCreate(['key' => 'email_otp_enabled'], ['value' => true]);
        Setting::clearCache('email_otp_enabled');
    }

    public function test_individual_registration_stores_identity_securely_and_sends_email_code(): void
    {
        Mail::fake();

        $response = $this->post('/kayit', [
            'account_type' => 'individual',
            'first_name' => 'Ayşe',
            'last_name' => 'Yılmaz',
            'identity_number' => '10000000146',
            'phone' => '905551112233',
            'city' => 'Osmaniye',
            'district' => 'Kadirli',
            'neighborhood' => '7 Mart Mahallesi',
            'email' => 'ayse@example.com',
            'password' => 'GucluSifre123',
            'password_confirmation' => 'GucluSifre123',
        ]);

        $response->assertRedirect(route('verification.notice'));
        $user = User::where('email', 'ayse@example.com')->firstOrFail();
        $this->assertSame(UserRole::INDIVIDUAL, $user->role);
        $this->assertSame(AccountStatus::PENDING_EMAIL, $user->account_status);
        $this->assertSame('10000000146', $user->identity_number);
        $this->assertSame(hash('sha256', '10000000146'), $user->identity_number_hash);
        $this->assertSame('Osmaniye', $user->city);
        $this->assertSame('Kadirli', $user->district);
        $this->assertSame('7 Mart Mahallesi', $user->neighborhood);
        $this->assertDatabaseHas('email_otp_codes', ['user_id' => $user->id, 'purpose' => OtpPurpose::EMAIL_VERIFICATION->value]);
        Mail::assertSent(OtpCodeMail::class, fn (OtpCodeMail $mail): bool => $mail->hasTo('ayse@example.com'));
    }

    public function test_registration_rejects_a_district_that_does_not_belong_to_the_city(): void
    {
        $response = $this->post('/kayit', [
            'account_type' => 'individual',
            'first_name' => 'Ayşe',
            'last_name' => 'Yılmaz',
            'identity_number' => '10000000146',
            'phone' => '905551112233',
            'city' => 'Osmaniye',
            'district' => 'Kadıköy',
            'neighborhood' => 'Fenerbahçe Mahallesi',
            'email' => 'ayse@example.com',
            'password' => 'GucluSifre123',
            'password_confirmation' => 'GucluSifre123',
        ]);

        $response->assertSessionHasErrors('district');
        $this->assertDatabaseMissing('users', ['email' => 'ayse@example.com']);
    }

    public function test_district_endpoint_uses_the_shared_location_source(): void
    {
        $this->getJson(route('locations.districts', ['city' => 'Osmaniye']))
            ->assertOk()
            ->assertJsonPath('data.Kadirli', 'Kadirli')
            ->assertJsonMissing(['Kadıköy']);
    }

    public function test_neighborhood_endpoint_returns_only_the_selected_districts_neighborhoods(): void
    {
        $this->getJson(route('locations.neighborhoods', [
            'city' => 'Osmaniye',
            'district' => 'Merkez',
        ]))
            ->assertOk()
            ->assertJsonFragment(['Alibeyli Mahallesi' => 'Alibeyli Mahallesi'])
            ->assertJsonMissing(['Fenerbahçe Mahallesi']);
    }

    public function test_neighborhood_endpoint_rejects_a_district_from_another_city(): void
    {
        $this->getJson(route('locations.neighborhoods', [
            'city' => 'Osmaniye',
            'district' => 'Kadıköy',
        ]))->assertUnprocessable();
    }

    public function test_registration_validation_messages_are_translated_to_turkish(): void
    {
        $this->app->setLocale('tr');
        User::factory()->create(['email' => 'ayse@example.com']);

        $response = $this->post('/kayit', [
            'account_type' => 'individual',
            'email' => 'ayse@example.com',
            'password' => 'sadeceharf',
        ]);

        $response->assertSessionHasErrors([
            'email' => 'Bu e-posta adresi daha önce kullanılmış.',
            'password' => 'şifre en az bir büyük ve bir küçük harf içermelidir.',
        ]);
    }

    public function test_business_becomes_pending_approval_after_email_verification(): void
    {
        Mail::fake();
        $user = User::factory()->create([
            'role' => UserRole::AGENT,
            'account_status' => AccountStatus::PENDING_EMAIL,
            'email_verified_at' => null,
        ]);
        $user->businessProfile()->create([
            'office_name' => 'Test Emlak', 'license_number' => 'YB-123', 'tax_office' => 'Merkez',
            'tax_number' => '1234567890', 'tax_number_hash' => hash('sha256', '1234567890'),
            'city' => 'Osmaniye', 'district' => 'Merkez', 'address' => 'Test adresi',
        ]);
        $this->createOtp($user, OtpPurpose::EMAIL_VERIFICATION, '123456');

        $response = $this->withSession(['verification_user_id' => $user->id])
            ->post('/e-posta-dogrula', ['code' => '123456']);

        $response->assertRedirect(route('login'));
        $this->assertSame(AccountStatus::PENDING_APPROVAL, $user->refresh()->account_status);
        $this->assertNotNull($user->email_verified_at);
        Mail::assertSent(ApprovalRequestedMail::class, fn (ApprovalRequestedMail $mail): bool => $mail->hasTo('info@zydemlak.com.tr'));
    }

    public function test_login_otp_is_single_use(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::INDIVIDUAL,
            'account_status' => AccountStatus::ACTIVE,
            'email_verified_at' => now(),
            'two_factor_enabled' => true,
        ]);
        $this->createPhoneOtp($user, '654321');

        $response = $this->withSession(['pending_login_user_id' => $user->id, 'pending_login_remember' => false])
            ->post('/iki-asamali-dogrulama', ['code' => '654321']);

        $response->assertRedirect('/portal');
        $this->assertAuthenticatedAs($user);
        $this->assertNotNull(PhoneOtpCode::first()->consumed_at);

        auth()->logout();
        $response = $this->withSession(['pending_login_user_id' => $user->id])
            ->post('/iki-asamali-dogrulama', ['code' => '654321']);
        $response->assertSessionHasErrors('code');
    }

    public function test_otp_locks_after_five_wrong_attempts(): void
    {
        $user = User::factory()->create(['two_factor_enabled' => true]);
        $this->createPhoneOtp($user, '123456');

        foreach (range(1, 5) as $attempt) {
            $this->withSession(['pending_login_user_id' => $user->id])
                ->post('/iki-asamali-dogrulama', ['code' => '000000']);
        }

        $otp = PhoneOtpCode::firstOrFail();
        $this->assertSame(5, $otp->attempts);
        $this->assertTrue($otp->locked_until->isFuture());
    }

    public function test_otp_resend_cooldown_message_uses_a_positive_whole_second(): void
    {
        Mail::fake();
        $this->travelTo(now()->startOfSecond());

        $user = User::factory()->create();
        $service = $this->app->make(EmailOtpService::class);
        $service->issue($user, OtpPurpose::LOGIN, '127.0.0.1');
        $this->travel(41)->seconds();

        try {
            $service->issue($user, OtpPurpose::LOGIN, '127.0.0.1');
            $this->fail('Yeniden gönderim bekleme süresi uygulanmadı.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                'Yeni kod zaten gönderildi. Tekrar göndermek için 19 saniye bekleyin.',
                $exception->errors()['code'][0]
            );
        }
    }

    private function createOtp(User $user, OtpPurpose $purpose, string $code): EmailOtpCode
    {
        return EmailOtpCode::create([
            'user_id' => $user->id,
            'purpose' => $purpose,
            'code_hash' => hash_hmac('sha256', $code, config('app.key')),
            'expires_at' => now()->addMinutes(5),
        ]);
    }

    private function createPhoneOtp(User $user, string $code): PhoneOtpCode
    {
        return PhoneOtpCode::create([
            'user_id' => $user->id,
            'code_hash' => hash_hmac('sha256', $code, config('app.key')),
            'expires_at' => now()->addMinutes(5),
        ]);
    }
}
