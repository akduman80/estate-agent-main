<?php

namespace Tests\Feature;

use App\Enums\AccountStatus;
use App\Enums\UserRole;
use App\Filament\Pages\ManageSettings;
use App\Models\Setting;
use App\Models\User;
use App\Services\DatabaseMailConfigurator;
use App\Services\OtpFeatureService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DatabaseMailSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_supervisor_can_open_the_smtp_and_otp_settings_page(): void
    {
        $supervisor = User::factory()->create([
            'role' => UserRole::SUPERVISOR,
            'account_status' => AccountStatus::ACTIVE,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($supervisor)
            ->get('/admin/manage-settings')
            ->assertOk()
            ->assertSee('SMTP e-posta servisini aktif et')
            ->assertSee('İleti Merkezi SMS bağlantısı')
            ->assertSee('API Key')
            ->assertSee('API Hash');

        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::actingAs($supervisor)
            ->test(ManageSettings::class)
            ->set('data.smtp_enabled', true)
            ->assertSee('SMTP bağlantı bilgileri')
            ->assertSee('E-posta OTP sistemini aktif et');
    }

    public function test_smtp_password_is_encrypted_and_saved_settings_are_applied(): void
    {
        $settings = [
            'smtp_enabled' => true,
            'smtp_host' => 'smtp.example.com',
            'smtp_port' => 587,
            'smtp_scheme' => 'auto',
            'smtp_username' => 'mailer@example.com',
            'smtp_from_address' => 'noreply@example.com',
            'smtp_from_name' => 'ZYDEMLAK',
        ];

        foreach ($settings as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
            Setting::clearCache($key);
        }

        Setting::putSecret('smtp_password', 'cok-gizli-parola');

        $rawPassword = Setting::query()->where('key', 'smtp_password')->value('value');

        $this->assertStringNotContainsString('cok-gizli-parola', (string) $rawPassword);
        $this->assertSame('cok-gizli-parola', Setting::getSecret('smtp_password'));
        $this->assertTrue(app(DatabaseMailConfigurator::class)->apply());
        $this->assertSame('smtp', config('mail.default'));
        $this->assertSame('smtp.example.com', config('mail.mailers.smtp.host'));
        $this->assertSame(587, config('mail.mailers.smtp.port'));
        $this->assertSame('cok-gizli-parola', config('mail.mailers.smtp.password'));
        $this->assertSame('noreply@example.com', config('mail.from.address'));
    }

    public function test_sms_api_credentials_are_encrypted(): void
    {
        Setting::putSecret('sms_api_key', 'gizli-api-key');
        Setting::putSecret('sms_api_hash', 'gizli-api-hash');

        $rawKey = (string) Setting::query()->where('key', 'sms_api_key')->value('value');
        $rawHash = (string) Setting::query()->where('key', 'sms_api_hash')->value('value');

        $this->assertStringNotContainsString('gizli-api-key', $rawKey);
        $this->assertStringNotContainsString('gizli-api-hash', $rawHash);
        $this->assertSame('gizli-api-key', Setting::getSecret('sms_api_key'));
        $this->assertSame('gizli-api-hash', Setting::getSecret('sms_api_hash'));
    }

    public function test_email_otp_policy_uses_saved_values_and_enforces_safe_bounds(): void
    {
        $settings = [
            'email_otp_ttl_minutes' => 12,
            'email_otp_max_attempts' => 7,
            'email_otp_lock_minutes' => 25,
            'email_otp_resend_seconds' => 90,
            'email_otp_hourly_limit' => 8,
        ];

        foreach ($settings as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
            Setting::clearCache($key);
        }

        $policy = app(OtpFeatureService::class)->emailPolicy();

        $this->assertSame(6, $policy['length']);
        $this->assertSame(12, $policy['ttl_minutes']);
        $this->assertSame(7, $policy['max_attempts']);
        $this->assertSame(25, $policy['lock_minutes']);
        $this->assertSame(90, $policy['resend_seconds']);
        $this->assertSame(8, $policy['hourly_limit']);

        Setting::updateOrCreate(['key' => 'email_otp_ttl_minutes'], ['value' => 999]);
        Setting::clearCache('email_otp_ttl_minutes');

        $this->assertSame(30, app(OtpFeatureService::class)->emailPolicy()['ttl_minutes']);
    }
}
