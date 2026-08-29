<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\OtpFeatureService;
use App\Services\PhoneOtpService;
use Illuminate\Http\Request;

class PhoneVerificationController extends Controller
{
    public function show(Request $request, OtpFeatureService $otpFeatures)
    {
        if (! $otpFeatures->phoneEnabled()) {
            return redirect('/portal')->with('status', 'Telefon OTP sistemi şu anda pasif; doğrulama istenmedi.');
        }

        return view('auth.verify-phone', ['user' => $request->user()]);
    }

    public function send(Request $request, PhoneOtpService $service, OtpFeatureService $otpFeatures)
    {
        if (! $otpFeatures->phoneEnabled()) {
            return redirect('/portal')->with('status', 'Telefon OTP sistemi şu anda pasif; kod gönderilmedi.');
        }

        $service->issue($request->user(), $request->ip());

        return back()->with('status', 'Telefon doğrulama kodu gönderildi.');
    }

    public function verify(Request $request, PhoneOtpService $service, OtpFeatureService $otpFeatures)
    {
        if (! $otpFeatures->phoneEnabled()) {
            return redirect('/portal')->with('status', 'Telefon OTP sistemi şu anda pasif; doğrulama istenmedi.');
        }

        $data = $request->validate(['code' => ['required', 'digits:6']]);
        $service->verify($request->user(), $data['code']);

        return redirect()->intended('/portal')->with('status', 'Telefon numaranız doğrulandı.');
    }
}
