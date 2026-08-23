<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\EidsIdentityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class EidsCallbackController extends Controller
{
    public function start(EidsIdentityService $eidsIdentityService)
    {
        abort_unless($eidsIdentityService->isConfiguredForSso(), 503, 'EIDS_COMPANY_CODE tanımlı değil.');

        return redirect()->away($eidsIdentityService->ssoUrl());
    }

    public function startForAccount(Request $request, EidsIdentityService $eidsIdentityService)
    {
        abort_unless($eidsIdentityService->isConfiguredForSso(), 503, 'EIDS_COMPANY_CODE tanımlı değil.');
        abort_unless($request->user()?->phone_verified_at, 409, 'EİDS işleminden önce telefon numaranızı OTP ile doğrulayın.');

        $request->session()->put('eids_identity_user_id', $request->user()->id);

        return redirect()->away($eidsIdentityService->ssoUrl());
    }

    public function callback(Request $request, EidsIdentityService $eidsIdentityService)
    {
        $authorizationCode = (string) $request->query('yetkiKodu', '');
        $status = (string) $request->query('durum', '');

        if (blank($authorizationCode)) {
            return response()->view('eids.callback', [
                'status' => 'error',
                'message' => $status ?: 'EİDS dönüşünde yetkiKodu bulunamadı.',
                'userCode' => null,
                'envLine' => null,
            ], 422);
        }

        $accountUser = $request->session()->has('eids_identity_user_id')
            ? User::query()->with('businessProfile')->find($request->session()->get('eids_identity_user_id'))
            : null;
        $gsmNumber = $accountUser?->phone ?: config('services.eids.gsm_no');
        $taxNumber = $accountUser?->role === UserRole::AGENT
            ? $accountUser->businessProfile?->tax_number
            : ($accountUser ? null : config('services.eids.tax_no'));

        if (! $eidsIdentityService->isConfiguredForUserCode($gsmNumber)) {
            return response()->view('eids.callback', [
                'status' => 'error',
                'message' => 'EIDS_USERNAME, EIDS_PASSWORD ve EIDS_GSM_NO tanımlı olmalı.',
                'userCode' => null,
                'envLine' => null,
            ], 503);
        }

        try {
            $result = $eidsIdentityService->requestUserCode($authorizationCode, $gsmNumber, $taxNumber, $accountUser === null);
        } catch (Throwable $exception) {
            Log::warning('EİDS kullanıcı kodu sorgusu başarısız.', [
                'message' => $exception->getMessage(),
                'durum' => $status,
            ]);

            return response()->view('eids.callback', [
                'status' => 'error',
                'message' => 'EİDS kullanıcı kodu alınamadı: '.$exception->getMessage(),
                'userCode' => null,
                'envLine' => null,
            ], 502);
        }

        $userCode = $result['user_code'];

        if (blank($userCode)) {
            return response()->view('eids.callback', [
                'status' => 'error',
                'message' => $result['error_message'] ?: 'EİDS kullanıcı kodu boş döndü.',
                'userCode' => null,
                'envLine' => null,
            ], 422);
        }

        Log::info('EİDS kullanıcı kodu alındı.', [
            'durum' => $status,
            'hata_kodu' => $result['error_code'],
        ]);

        if ($accountUser) {
            $accountUser->forceFill([
                'eids_user_code' => $userCode,
                'eids_identity_verified_at' => now(),
            ])->save();
            $request->session()->forget('eids_identity_user_id');

            return view('eids.callback', [
                'status' => 'success',
                'message' => 'EİDS kimlik doğrulamanız hesabınıza güvenli biçimde kaydedildi.',
                'userCode' => null,
                'envLine' => null,
            ]);
        }

        return view('eids.callback', [
            'status' => 'success',
            'message' => 'EİDS kullanıcı kodu alındı. Bu değeri canlı .env dosyasında EIDS_USER_CODE alanına yazın.',
            'userCode' => $userCode,
            'envLine' => 'EIDS_USER_CODE='.$userCode,
        ]);
    }
}
