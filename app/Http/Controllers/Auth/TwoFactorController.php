<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\PhoneOtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TwoFactorController extends Controller
{
    public function show(Request $request)
    {
        abort_unless($request->session()->has('pending_login_user_id'), 403);
        $user = User::findOrFail($request->session()->get('pending_login_user_id'));

        if (! $user->two_factor_enabled) {
            return $this->completeWithoutOtp($request);
        }

        return view('auth.two-factor', [
            'maskedPhone' => $this->maskPhone($user->phone),
        ]);
    }

    public function verify(Request $request, PhoneOtpService $otpService)
    {
        abort_unless($request->session()->has('pending_login_user_id'), 403);
        $data = $request->validate(['code' => ['required', 'digits:6']]);
        $user = User::findOrFail($request->session()->get('pending_login_user_id'));

        if (! $user->two_factor_enabled) {
            return $this->completeWithoutOtp($request);
        }

        $otpService->verify($user, $data['code']);

        Auth::login($user, (bool) $request->session()->pull('pending_login_remember', false));
        $request->session()->regenerate();
        $request->session()->forget('pending_login_user_id');
        $user->forceFill(['last_two_factor_at' => now()])->save();

        return redirect()->intended($user->role->getRedirectPath());
    }

    public function resend(Request $request, PhoneOtpService $otpService)
    {
        abort_unless($request->session()->has('pending_login_user_id'), 403);
        $user = User::findOrFail($request->session()->get('pending_login_user_id'));

        if (! $user->two_factor_enabled) {
            return $this->completeWithoutOtp($request);
        }

        $otpService->issue($user, $request->ip());

        return back()->with('status', 'Yeni giriş kodu telefonunuza gönderildi.');
    }

    private function completeWithoutOtp(Request $request)
    {
        abort_unless($request->session()->has('pending_login_user_id'), 403);
        $user = User::findOrFail($request->session()->get('pending_login_user_id'));

        Auth::login($user, (bool) $request->session()->pull('pending_login_remember', false));
        $request->session()->regenerate();
        $request->session()->forget('pending_login_user_id');

        return redirect()->intended($user->role->getRedirectPath())
            ->with('status', 'İki aşamalı doğrulama kapalı olduğu için giriş kodu istenmedi.');
    }

    private function maskPhone(?string $phone): string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone) ?? '';

        return strlen($digits) >= 4
            ? str_repeat('*', max(0, strlen($digits) - 4)).substr($digits, -4)
            : 'kayıtlı telefonunuz';
    }
}
