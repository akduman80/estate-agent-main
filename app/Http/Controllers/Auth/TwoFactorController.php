<?php

namespace App\Http\Controllers\Auth;

use App\Enums\OtpPurpose;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\EmailOtpService;
use App\Services\OtpFeatureService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TwoFactorController extends Controller
{
    public function show(Request $request, OtpFeatureService $otpFeatures)
    {
        abort_unless($request->session()->has('pending_login_user_id'), 403);

        if (! $otpFeatures->emailEnabled()) {
            return $this->completeWithoutOtp($request);
        }

        return view('auth.two-factor');
    }

    public function verify(Request $request, EmailOtpService $otpService, OtpFeatureService $otpFeatures)
    {
        if (! $otpFeatures->emailEnabled()) {
            return $this->completeWithoutOtp($request);
        }

        $data = $request->validate(['code' => ['required', 'digits:6']]);
        $user = User::findOrFail($request->session()->get('pending_login_user_id'));
        $otpService->verify($user, OtpPurpose::LOGIN, $data['code']);

        Auth::login($user, (bool) $request->session()->pull('pending_login_remember', false));
        $request->session()->regenerate();
        $request->session()->forget('pending_login_user_id');
        $user->forceFill(['last_two_factor_at' => now()])->save();

        return redirect()->intended($user->role->getRedirectPath());
    }

    public function resend(Request $request, EmailOtpService $otpService, OtpFeatureService $otpFeatures)
    {
        if (! $otpFeatures->emailEnabled()) {
            return $this->completeWithoutOtp($request);
        }

        $user = User::findOrFail($request->session()->get('pending_login_user_id'));
        $otpService->issue($user, OtpPurpose::LOGIN, $request->ip());

        return back()->with('status', 'Yeni giriş kodu gönderildi.');
    }

    private function completeWithoutOtp(Request $request)
    {
        abort_unless($request->session()->has('pending_login_user_id'), 403);
        $user = User::findOrFail($request->session()->get('pending_login_user_id'));

        Auth::login($user, (bool) $request->session()->pull('pending_login_remember', false));
        $request->session()->regenerate();
        $request->session()->forget('pending_login_user_id');

        return redirect()->intended($user->role->getRedirectPath())
            ->with('status', 'OTP pasif olduğu için giriş kodu istenmedi.');
    }
}
