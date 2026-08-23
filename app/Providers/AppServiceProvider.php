<?php

namespace App\Providers;

use App\Contracts\PhoneOtpSender;
use App\Http\Responses\LoginResponse;
use App\Services\LogPhoneOtpSender;
use Filament\Http\Responses\Auth\Contracts\LoginResponse as LoginResponseContract;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(LoginResponseContract::class, LoginResponse::class);
        $this->app->bind(PhoneOtpSender::class, LogPhoneOtpSender::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        ResetPassword::createUrlUsing(fn ($user, string $token): string => route('password.reset', [
            'token' => $token,
            'email' => $user->getEmailForPasswordReset(),
        ]));
        //
    }
}
