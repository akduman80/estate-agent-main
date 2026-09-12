<?php

namespace Tests\Feature;

use App\Contracts\PhoneOtpSender;
use App\Enums\AccountStatus;
use App\Enums\UserRole;
use App\Filament\Pages\Auth\Login;
use App\Models\PhoneOtpCode;
use App\Models\Setting;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class LoginTwoFactorTest extends TestCase
{
    use RefreshDatabase;

    public function test_individual_with_two_factor_disabled_still_receives_sms_login_code(): void
    {
        $sender = new class implements PhoneOtpSender
        {
            public string $phone = '';

            public string $code = '';

            public function send(string $phone, string $code): void
            {
                $this->phone = $phone;
                $this->code = $code;
            }
        };
        $this->app->instance(PhoneOtpSender::class, $sender);
        $user = $this->createPortalUser(false);

        Filament::setCurrentPanel(Filament::getPanel('portal'));

        Livewire::test(Login::class)
            ->fillForm([
                'email' => $user->email,
                'password' => 'GucluSifre123',
                'remember' => false,
            ])
            ->call('authenticate')
            ->assertRedirect(route('two-factor.show'));

        $this->assertGuest();
        $this->assertSame($user->phone, $sender->phone);
        $this->assertMatchesRegularExpression('/^\d{6}$/', $sender->code);
        $this->assertDatabaseCount('phone_otp_codes', 1);
    }

    public function test_agent_with_two_factor_enabled_receives_sms_and_cannot_login_before_code(): void
    {
        $sender = new class implements PhoneOtpSender
        {
            public string $phone = '';

            public string $code = '';

            public function send(string $phone, string $code): void
            {
                $this->phone = $phone;
                $this->code = $code;
            }
        };
        $this->app->instance(PhoneOtpSender::class, $sender);
        $user = $this->createPortalUser(true, UserRole::AGENT);

        Filament::setCurrentPanel(Filament::getPanel('portal'));

        Livewire::test(Login::class)
            ->fillForm([
                'email' => $user->email,
                'password' => 'GucluSifre123',
                'remember' => false,
            ])
            ->call('authenticate')
            ->assertRedirect(route('two-factor.show'));

        $this->assertGuest();
        $this->assertSame($user->phone, $sender->phone);
        $this->assertMatchesRegularExpression('/^\d{6}$/', $sender->code);
        $this->assertDatabaseCount('phone_otp_codes', 1);

        $this->withSession([
            'pending_login_user_id' => $user->id,
            'pending_login_remember' => false,
        ])->post(route('two-factor.verify'), ['code' => $sender->code])
            ->assertRedirect('/portal');

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull(PhoneOtpCode::firstOrFail()->consumed_at);
        $this->assertNotNull($user->refresh()->last_two_factor_at);
    }

    public function test_user_can_enable_and_disable_two_factor_with_current_password(): void
    {
        $user = $this->createPortalUser(false);
        $this->configureSmsProvider();

        $this->actingAs($user)->post(route('account.two-factor.update'), [
            'enabled' => '1',
            'current_password' => 'GucluSifre123',
        ])->assertSessionHas('status');

        $this->assertTrue($user->refresh()->two_factor_enabled);

        $this->actingAs($user)->post(route('account.two-factor.update'), [
            'enabled' => '0',
            'current_password' => 'GucluSifre123',
        ])->assertSessionHas('status');

        $this->assertFalse($user->refresh()->two_factor_enabled);
    }

    public function test_two_factor_cannot_be_enabled_when_sms_service_is_not_configured(): void
    {
        $user = $this->createPortalUser(false);

        $this->actingAs($user)->post(route('account.two-factor.update'), [
            'enabled' => '1',
            'current_password' => 'GucluSifre123',
        ])->assertSessionHasErrors('enabled');

        $this->assertFalse($user->refresh()->two_factor_enabled);
    }

    private function createPortalUser(bool $twoFactorEnabled, UserRole $role = UserRole::INDIVIDUAL): User
    {
        return User::factory()->create([
            'email' => fake()->unique()->safeEmail(),
            'phone' => '905551112233',
            'phone_verified_at' => now(),
            'password' => Hash::make('GucluSifre123'),
            'role' => $role,
            'account_status' => AccountStatus::ACTIVE,
            'email_verified_at' => now(),
            'two_factor_enabled' => $twoFactorEnabled,
        ]);
    }

    private function configureSmsProvider(): void
    {
        Setting::putSecret('sms_api_key', 'test-key');
        Setting::putSecret('sms_api_hash', 'test-hash');

        foreach ([
            'sms_sender' => 'ILHAN AKDMN',
            'sms_iys_enabled' => '0',
            'sms_iys_list' => 'BIREYSEL',
        ] as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
            Setting::clearCache($key);
        }
    }
}
