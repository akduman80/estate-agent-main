<?php

namespace App\Filament\Pages\Auth;

use App\Enums\AccountStatus;
use App\Enums\OtpPurpose;
use App\Enums\UserRole;
use App\Models\User;
use App\Services\EmailOtpService;
use App\Services\OtpFeatureService;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\TextInput;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\ValidationException;

class Login extends \Filament\Pages\Auth\Login
{
    protected static string $view = 'filament.pages.auth.login';

    public function authenticate(): ?LoginResponse
    {
        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $exception) {
            Notification::make()->title('Çok fazla giriş denemesi')->body($exception->secondsUntilAvailable.' saniye sonra tekrar deneyin.')->danger()->send();

            return null;
        }

        $data = $this->form->getState();
        $user = User::query()->where('email', mb_strtolower($data['email']))->first();
        $emailOtpEnabled = app(OtpFeatureService::class)->emailEnabled();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages(['data.email' => 'E-posta adresi veya şifre hatalı.']);
        }

        if (! $user->email_verified_at && $emailOtpEnabled) {
            session()->put('verification_user_id', $user->id);

            return app(LoginResponse::class);
        }

        if (! $user->email_verified_at) {
            $user->forceFill([
                'email_verified_at' => now(),
                'account_status' => $user->role === UserRole::AGENT
                    ? AccountStatus::PENDING_APPROVAL
                    : AccountStatus::ACTIVE,
            ])->save();
        }

        if ($user->account_status !== AccountStatus::ACTIVE) {
            throw ValidationException::withMessages(['data.email' => match ($user->account_status) {
                AccountStatus::PENDING_APPROVAL => 'Hesabınız yönetici onayı bekliyor.',
                AccountStatus::SUSPENDED => 'Hesabınız askıya alınmış.',
                AccountStatus::REJECTED => 'Hesap başvurunuz reddedilmiş.',
                default => 'Hesabınız henüz aktif değil.',
            }]);
        }

        if (! $emailOtpEnabled) {
            Auth::login($user, (bool) ($data['remember'] ?? false));
            session()->regenerate();

            return app(LoginResponse::class);
        }

        app(EmailOtpService::class)->issue($user, OtpPurpose::LOGIN, request()->ip());
        session()->put([
            'pending_login_user_id' => $user->id,
            'pending_login_remember' => (bool) ($data['remember'] ?? false),
        ]);

        return app(LoginResponse::class);
    }

    public function getHeading(): string
    {
        return 'ZYDEMLAK güvenli giriş';
    }

    protected function getPasswordFormComponent(): Component
    {
        return TextInput::make('password')
            ->label('Şifre')
            ->hint(new HtmlString('<a class="text-primary-600 hover:underline" href="'.route('password.request').'">Şifremi unuttum</a>'))
            ->password()
            ->revealable()
            ->autocomplete('current-password')
            ->required();
    }

    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('email')->label('E-posta adresi')->email()->required()->autocomplete()->autofocus();
    }

    protected function getRememberFormComponent(): Component
    {
        return Checkbox::make('remember')->label('Beni hatırla');
    }

    protected function getAuthenticateFormAction(): Action
    {
        return Action::make('authenticate')->label('Giriş yap')->submit('authenticate');
    }

    public function registerAction(): Action
    {
        return Action::make('register')->link()->label('Üye ol')->url(route('register'));
    }
}
