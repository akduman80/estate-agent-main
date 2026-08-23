<?php

namespace App\Http\Controllers;

use App\Enums\OtpPurpose;
use App\Mail\SecurityNoticeMail;
use App\Services\EmailOtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules\Password;

class AccountSecurityController extends Controller
{
    public function show(Request $request)
    {
        return view('account.security', ['user' => $request->user()]);
    }

    public function requestEmailChange(Request $request, EmailOtpService $service)
    {
        $data = $request->validate([
            'new_email' => ['required', 'email:rfc', 'max:255', 'unique:users,email'],
            'current_password' => ['required', 'current_password'],
        ]);

        $newEmail = mb_strtolower($data['new_email']);
        $request->session()->put('pending_new_email', $newEmail);
        $service->issue($request->user(), OtpPurpose::EMAIL_CHANGE, $request->ip(), $newEmail);

        Mail::to($request->user()->email)->send(new SecurityNoticeMail(
            $request->user(),
            'Hesabınızın e-posta adresini '.$newEmail.' olarak değiştirmek için işlem başlatıldı.'
        ));

        return back()->with('status', 'Yeni e-posta adresine doğrulama kodu gönderildi.');
    }

    public function confirmEmailChange(Request $request, EmailOtpService $service)
    {
        $data = $request->validate(['code' => ['required', 'digits:6']]);
        $newEmail = $request->session()->get('pending_new_email');
        abort_unless($newEmail, 409, 'Bekleyen e-posta değişikliği bulunamadı.');

        $service->verify($request->user(), OtpPurpose::EMAIL_CHANGE, $data['code']);
        $oldEmail = $request->user()->email;
        $request->user()->forceFill(['email' => $newEmail, 'email_verified_at' => now()])->save();
        DB::table('sessions')->where('user_id', $request->user()->id)->where('id', '!=', $request->session()->getId())->delete();
        $request->session()->forget('pending_new_email');
        Mail::to($oldEmail)->send(new SecurityNoticeMail($request->user(), 'Hesabınızın e-posta adresi '.$newEmail.' olarak değiştirildi.'));

        return back()->with('status', 'E-posta adresiniz doğrulanarak değiştirildi.');
    }

    public function requestPasswordChange(Request $request, EmailOtpService $service)
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(10)->letters()->mixedCase()->numbers()],
        ]);

        $request->session()->put('pending_password_hash', Hash::make($data['password']));
        $service->issue($request->user(), OtpPurpose::SECURITY, $request->ip());

        return back()->with('status', 'Şifre değişikliğini tamamlamak için e-posta kodu gönderildi.');
    }

    public function confirmPasswordChange(Request $request, EmailOtpService $service)
    {
        $data = $request->validate(['code' => ['required', 'digits:6']]);
        $passwordHash = $request->session()->get('pending_password_hash');
        abort_unless($passwordHash, 409, 'Bekleyen şifre değişikliği bulunamadı.');

        $service->verify($request->user(), OtpPurpose::SECURITY, $data['code']);
        $request->user()->forceFill(['password' => $passwordHash])->save();
        DB::table('sessions')->where('user_id', $request->user()->id)->where('id', '!=', $request->session()->getId())->delete();
        $request->session()->forget('pending_password_hash');

        return back()->with('status', 'Şifreniz güvenli biçimde değiştirildi.');
    }
}
