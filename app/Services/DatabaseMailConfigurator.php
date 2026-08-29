<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Throwable;

class DatabaseMailConfigurator
{
    public function apply(): bool
    {
        try {
            if (! Schema::hasTable('settings') || ! $this->enabled() || ! $this->hasStoredConfiguration()) {
                return false;
            }

            $scheme = Setting::get('smtp_scheme', 'auto');

            config([
                'mail.default' => 'smtp',
                'mail.mailers.smtp.url' => null,
                'mail.mailers.smtp.scheme' => $scheme === 'auto' ? null : $scheme,
                'mail.mailers.smtp.host' => Setting::get('smtp_host'),
                'mail.mailers.smtp.port' => (int) Setting::get('smtp_port', 587),
                'mail.mailers.smtp.username' => Setting::get('smtp_username'),
                'mail.mailers.smtp.password' => Setting::getSecret('smtp_password'),
                'mail.from.address' => Setting::get('smtp_from_address'),
                'mail.from.name' => Setting::get('smtp_from_name', config('app.name')),
            ]);

            Mail::purge('smtp');

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    public function enabled(): bool
    {
        return filter_var(Setting::get('smtp_enabled', false), FILTER_VALIDATE_BOOL);
    }

    public function hasStoredConfiguration(): bool
    {
        return filled(Setting::get('smtp_host'))
            && filled(Setting::get('smtp_port'))
            && filled(Setting::get('smtp_username'))
            && filled(Setting::get('smtp_from_address'))
            && filled(Setting::getSecret('smtp_password'));
    }
}
