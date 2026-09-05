<?php

namespace Tests\Feature;

use App\Enums\AccountStatus;
use App\Enums\UserRole;
use App\Filament\Pages\Auth\Login;
use App\Mail\OtpCodeMail;
use App\Models\Setting;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class OtpFeatureToggleTest extends TestCase
{
    use RefreshDatabase;

    public function test_individual_registration_skips_email_code_when_otp_is_passive(): void
    {
        Mail::fake();
        $this->setEmailOtp(false);

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

        $response->assertRedirect(route('login'));
        $user = User::query()->where('email', 'ayse@example.com')->firstOrFail();
        $this->assertSame(AccountStatus::ACTIVE, $user->account_status);
        $this->assertNotNull($user->email_verified_at);
        $this->assertDatabaseCount('email_otp_codes', 0);
        Mail::assertNotSent(OtpCodeMail::class);
    }

    public function test_login_authenticates_directly_when_otp_is_passive(): void
    {
        $this->setEmailOtp(false);
        $user = User::factory()->create([
            'email' => 'uye@example.com',
            'password' => Hash::make('GucluSifre123'),
            'role' => UserRole::INDIVIDUAL,
            'account_status' => AccountStatus::ACTIVE,
            'email_verified_at' => now(),
        ]);

        Filament::setCurrentPanel(Filament::getPanel('portal'));

        Livewire::test(Login::class)
            ->fillForm([
                'email' => 'uye@example.com',
                'password' => 'GucluSifre123',
                'remember' => false,
            ])
            ->call('authenticate')
            ->assertRedirect('/portal');

        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseCount('email_otp_codes', 0);
    }

    public function test_password_change_uses_current_password_without_otp_when_passive(): void
    {
        $this->setEmailOtp(false);
        $user = User::factory()->create([
            'password' => Hash::make('EskiSifre123'),
            'account_status' => AccountStatus::ACTIVE,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)->post(route('account.password.request'), [
            'current_password' => 'EskiSifre123',
            'password' => 'YeniSifre456',
            'password_confirmation' => 'YeniSifre456',
        ])->assertRedirect();

        $this->assertTrue(Hash::check('YeniSifre456', $user->refresh()->password));
        $this->assertDatabaseCount('email_otp_codes', 0);
    }

    private function setEmailOtp(bool $enabled): void
    {
        Setting::updateOrCreate(['key' => 'email_otp_enabled'], ['value' => $enabled]);
        Setting::clearCache('email_otp_enabled');
    }
}
