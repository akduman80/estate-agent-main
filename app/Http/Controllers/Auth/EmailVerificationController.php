<?php

namespace App\Http\Controllers\Auth;

use App\Enums\AccountStatus;
use App\Enums\OtpPurpose;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Mail\ApprovalRequestedMail;
use App\Models\User;
use App\Services\EmailOtpService;
use App\Services\OtpFeatureService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class EmailVerificationController extends Controller
{
    public function show(Request $request, OtpFeatureService $otpFeatures)
    {
        abort_unless($request->session()->has('verification_user_id'), 403);

        if (! $otpFeatures->emailEnabled()) {
            return $this->completeWithoutOtp($request);
        }

        return view('auth.verify-email');
    }

    public function verify(Request $request, EmailOtpService $otpService, OtpFeatureService $otpFeatures)
    {
        if (! $otpFeatures->emailEnabled()) {
            return $this->completeWithoutOtp($request);
        }

        $data = $request->validate(['code' => ['required', 'digits:6']]);
        $user = User::findOrFail($request->session()->get('verification_user_id'));
        $otpService->verify($user, OtpPurpose::EMAIL_VERIFICATION, $data['code']);

        $isBusiness = $user->role === UserRole::AGENT;
        \Illuminate\Support\Facades\DB::transaction(function () use ($user, $isBusiness): void {
            $user->forceFill([
                'email_verified_at' => now(),
                'account_status' => $isBusiness ? AccountStatus::PENDING_APPROVAL : AccountStatus::ACTIVE,
            ])->save();
            app(\App\Services\WhatsappNotifications::class)->member($user);
        });

        $request->session()->forget('verification_user_id');

        if ($isBusiness) {
            Mail::to(config('security.approval_email'))->send(new ApprovalRequestedMail('emlak işletmesi üyeliği', $user, $user->businessProfile?->id));
        }

        return redirect()->route('login')->with('status', $isBusiness
            ? 'E-posta adresiniz doğrulandı. İşletme hesabınız yönetici onayına gönderildi.'
            : 'E-posta adresiniz doğrulandı. Giriş yapabilirsiniz.');
    }

    public function resend(Request $request, EmailOtpService $otpService, OtpFeatureService $otpFeatures)
    {
        if (! $otpFeatures->emailEnabled()) {
            return $this->completeWithoutOtp($request);
        }

        $user = User::findOrFail($request->session()->get('verification_user_id'));
        $otpService->issue($user, OtpPurpose::EMAIL_VERIFICATION, $request->ip());

        return back()->with('status', 'Yeni doğrulama kodu gönderildi.');
    }

    private function completeWithoutOtp(Request $request)
    {
        $user = User::findOrFail($request->session()->get('verification_user_id'));
        $isBusiness = $user->role === UserRole::AGENT;

        \Illuminate\Support\Facades\DB::transaction(function () use ($user, $isBusiness): void {
            $user->forceFill([
                'email_verified_at' => now(),
                'account_status' => $isBusiness ? AccountStatus::PENDING_APPROVAL : AccountStatus::ACTIVE,
            ])->save();
            app(\App\Services\WhatsappNotifications::class)->member($user);
        });
        $request->session()->forget('verification_user_id');

        if ($isBusiness) {
            Mail::to(config('security.approval_email'))->send(new ApprovalRequestedMail('emlak işletmesi üyeliği', $user, $user->businessProfile?->id));
        }

        return redirect()->route('login')->with('status', $isBusiness
            ? 'OTP pasif. İşletme hesabınız supervisor onayına gönderildi.'
            : 'OTP pasif. Hesabınız aktifleştirildi; giriş yapabilirsiniz.');
    }
}
