<?php

namespace App\Services;

use App\Models\Setting;

class EidsSettingsService
{
    public function companyCode(): ?string
    {
        return $this->setting('eids_company_code', config('services.eids.company_code'));
    }

    public function username(): ?string
    {
        return $this->secret('eids_username', config('services.eids.username'));
    }

    public function password(): ?string
    {
        return $this->secret('eids_password', config('services.eids.password'));
    }

    public function platformUserCode(): ?string
    {
        return $this->secret('eids_user_code', config('services.eids.user_code'));
    }

    public function platformTaxNumber(): ?string
    {
        return $this->secret('eids_tax_no', config('services.eids.tax_no'));
    }

    public function platformGsmNumber(): ?string
    {
        return $this->secret('eids_gsm_no', config('services.eids.gsm_no'));
    }

    public function baseUrl(): string
    {
        return rtrim($this->setting('eids_base_url', config('services.eids.base_url', 'https://ws.gtb.gov.tr:8443')), '/');
    }

    public function verifyPath(): string
    {
        return $this->setting('eids_verify_path', config('services.eids.verify_path', '/EidsTasinmazAPI'));
    }

    public function userCodePath(): string
    {
        return $this->setting('eids_user_code_path', config('services.eids.user_code_path', '/EidsApi/Kullanici/GetKullaniciKodu'));
    }

    public function healthPath(): string
    {
        return $this->setting('eids_health_path', config('services.eids.health_path', '/EidsApi/health'));
    }

    public function ssoUrl(): string
    {
        return $this->setting('eids_sso_url', config('services.eids.sso_url', 'https://eids.ticaret.gov.tr/oturum'));
    }

    public function callbackUrl(): ?string
    {
        return $this->setting('eids_callback_url', config('services.eids.callback_url'));
    }

    public function timeout(): int
    {
        return max(1, (int) Setting::get('eids_timeout', config('services.eids.timeout', 20)));
    }

    public function verifyUrl(): string
    {
        return $this->baseUrl().'/'.ltrim($this->verifyPath(), '/');
    }

    public function userCodeUrl(): string
    {
        return $this->baseUrl().'/'.ltrim($this->userCodePath(), '/');
    }

    private function setting(string $key, mixed $fallback = null): ?string
    {
        $value = Setting::get($key);

        return filled($value) ? (string) $value : (filled($fallback) ? (string) $fallback : null);
    }

    private function secret(string $key, mixed $fallback = null): ?string
    {
        $value = Setting::getSecret($key);

        return filled($value) ? $value : (filled($fallback) ? (string) $fallback : null);
    }
}
