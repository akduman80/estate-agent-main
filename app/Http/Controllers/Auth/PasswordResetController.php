<?php

namespace App\Http\Controllers\Auth;

use App\Enums\OtpPurpose;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\EmailOtpService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;

class PasswordResetController extends Controller
{
    public function requestForm(Request $request)
    {
        return view('auth.forgot-password', [
            'email' => $request->session()->get('password_reset_email'),
        ]);
    }

    public function sendLink(Request $request, EmailOtpService $otpService)
    {
        $data = $request->validate(['email' => ['required', 'email']]);
        $user = User::query()->where('email', $data['email'])->first();

        if ($user) {
            $otpService->issue($user, OtpPurpose::PASSWORD_RESET, $request->ip());
        }

        $request->session()->put('password_reset_email', $data['email']);

        return redirect()
            ->route('password.reset')
            ->with('status', 'Bu e-posta sistemde kayıtlıysa 6 haneli şifre sıfırlama kodu gönderildi.');
    }

    public function resetForm(Request $request, ?string $token = null)
    {
        return view('auth.reset-password', [
            'email' => $request->query('email', $request->session()->get('password_reset_email')),
        ]);
    }

    public function reset(Request $request, EmailOtpService $otpService)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'code' => ['required', 'digits:6'],
            'password' => ['required', 'confirmed', PasswordRule::min(10)->letters()->mixedCase()->numbers()],
        ]);

        $user = User::query()->where('email', $data['email'])->first();

        if (! $user) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['code' => 'Kod geçersiz veya süresi dolmuş.']);
        }

        $otpService->verify($user, OtpPurpose::PASSWORD_RESET, $data['code']);

        $user->forceFill([
            'password' => Hash::make($data['password']),
            'remember_token' => Str::random(60),
        ])->save();

        DB::table('sessions')->where('user_id', $user->id)->delete();
        event(new PasswordReset($user));
        $request->session()->forget('password_reset_email');

        return redirect()->route('login')->with('status', 'Şifreniz yenilendi. Yeni şifrenizle giriş yapabilirsiniz.');
    }
}
