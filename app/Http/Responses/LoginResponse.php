<?php

/** Created by ramazanorak, Date: 26.07.2026 */

namespace App\Http\Responses;

use Filament\Http\Responses\Auth\Contracts\LoginResponse as LoginResponseContract;
use Illuminate\Http\RedirectResponse;
use Livewire\Features\SupportRedirects\Redirector;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request): RedirectResponse|Redirector
    {
        if ($request->session()->has('pending_login_user_id')) {
            return redirect()->route('two-factor.show');
        }

        if ($request->session()->has('verification_user_id')) {
            return redirect()->route('verification.notice');
        }

        $user = auth()->user();

        // Rolün getRedirectPath() metodunu kullanarak yönlendir
        return redirect()->to($user->role->getRedirectPath());
    }
}
