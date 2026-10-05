<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\ListingEidsRequestLog;
use App\Models\Setting;
use App\Models\User;
use App\Services\EidsIdentityService;
use App\Services\EidsSettingsService;
use App\Services\OtpFeatureService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class EidsCallbackController extends Controller
{
    public function start(Request $request, EidsIdentityService $eidsIdentityService)
    {
        abort_unless($request->user()?->role === UserRole::SUPERVISOR, 403, 'Yalnızca yöneticiler ZYDEMLAK platform EİDS kodunu yenileyebilir.');
        abort_unless($eidsIdentityService->isConfiguredForSso(), 503, 'EIDS_COMPANY_CODE tanımlı değil.');

        $request->session()->forget('eids_identity_user_id');
        $request->session()->put('eids_platform_identity', true);

        return redirect()->away($eidsIdentityService->ssoUrl());
    }

    public function startForAccount(Request $request, EidsIdentityService $eidsIdentityService, OtpFeatureService $otpFeatures)
    {
        abort_unless($eidsIdentityService->isConfiguredForSso(), 503, 'EIDS_COMPANY_CODE tanımlı değil.');
        abort_unless(
            ! $otpFeatures->phoneEnabled() || $request->user()?->phone_verified_at,
            409,
            'EİDS işleminden önce telefon numaranızı OTP ile doğrulayın.'
        );

        abort_unless(filled($request->user()->phone), 409, 'Önce hesabınıza telefon numaranızı ekleyin.');
        abort_if(
            $request->user()->role === UserRole::AGENT && blank($request->user()->businessProfile?->tax_number),
            409,
            'Önce emlak ofisinizin vergi numarasını tamamlayın.'
        );

        $request->session()->forget('eids_platform_identity');
        $request->session()->put('eids_identity_user_id', $request->user()->id);

        return redirect()->away($eidsIdentityService->ssoUrl());
    }

    public function callback(Request $request, EidsIdentityService $eidsIdentityService, EidsSettingsService $eidsSettings)
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
        $isPlatformFlow = (bool) $request->session()->get('eids_platform_identity', false);
        if (! $accountUser && ! $isPlatformFlow && $request->user()) {
            $accountUser = $request->user()->load('businessProfile');
        }
        if ($accountUser) {
            abort_unless($request->user()?->id === $accountUser->id, 403, 'EİDS işlemini başlattığınız hesapla giriş yapın.');
        }

        if (! $accountUser && ! $isPlatformFlow) {
            ListingEidsRequestLog::create([
                'endpoint' => $request->url(),
                'method' => 'GET',
                'request_payload' => ['yetkiKodu' => $authorizationCode, 'durum' => $status],
                'result_status' => 'error',
                'error_message' => 'EİDS callback oturumu bulunamadı. Kullanıcı kodu servisine istek gönderilmedi.',
                'attempted_at' => now(),
                'response_payload' => [
                    'stage' => 'callback_session_check',
                    'callback_host' => $request->getHost(),
                    'configured_callback_host' => parse_url($eidsSettings->callbackUrl() ?? '', PHP_URL_HOST),
                    'session_cookie_present' => $request->cookies->has(config('session.cookie')),
                    'authenticated' => false,
                ],
            ]);

            return response()->view('eids.callback', [
                'status' => 'error',
                'message' => 'EİDS işlemi başlatılan oturumla eşleşmedi. Lütfen hesabınızla giriş yapıp EİDS doğrulamasını tekrar başlatın.',
                'userCode' => null,
                'envLine' => null,
                'retryUrl' => route('account.eids.start'),
            ], 409);
        }

        $gsmNumber = $accountUser ? $accountUser->phone : $eidsSettings->platformGsmNumber();
        $taxNumber = $accountUser?->role === UserRole::AGENT
            ? $accountUser->businessProfile?->tax_number
            : ($accountUser ? null : $eidsSettings->platformTaxNumber());

        if (! $eidsIdentityService->isConfiguredForUserCode($gsmNumber)) {
            return response()->view('eids.callback', [
                'status' => 'error',
                'message' => $accountUser
                    ? 'EIDS_USERNAME ve EIDS_PASSWORD tanımlı olmalı; ayrıca hesabınızdaki telefon numarası eksiksiz olmalıdır.'
                    : 'EIDS_USERNAME, EIDS_PASSWORD ve EIDS_GSM_NO tanımlı olmalı.',
                'userCode' => null,
                'envLine' => null,
            ], 503);
        }

        try {
            $result = $eidsIdentityService->requestUserCode($authorizationCode, $gsmNumber, $taxNumber, $accountUser === null, $accountUser);
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
            $request->session()->forget('eids_platform_identity');

            return view('eids.callback', [
                'status' => 'success',
                'message' => 'EİDS kimlik doğrulamanız hesabınıza güvenli biçimde kaydedildi.',
                'userCode' => null,
                'envLine' => null,
            ]);
        }

        Setting::putSecret('eids_user_code', $userCode);
        $request->session()->forget('eids_platform_identity');

        return view('eids.callback', [
            'status' => 'success',
            'message' => 'ZYDEMLAK platform EİDS kullanıcı kodu güvenli biçimde veritabanına kaydedildi.',
            'userCode' => null,
            'envLine' => null,
        ]);
    }
}
