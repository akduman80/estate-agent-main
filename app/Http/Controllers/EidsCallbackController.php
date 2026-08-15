<?php

namespace App\Http\Controllers;

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

        if (! $eidsIdentityService->isConfiguredForUserCode()) {
            return response()->view('eids.callback', [
                'status' => 'error',
                'message' => 'EIDS_USERNAME, EIDS_PASSWORD ve EIDS_GSM_NO tanımlı olmalı.',
                'userCode' => null,
                'envLine' => null,
            ], 503);
        }

        try {
            $result = $eidsIdentityService->requestUserCode($authorizationCode, $request->query('gsmNo'));
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

        return view('eids.callback', [
            'status' => 'success',
            'message' => 'EİDS kullanıcı kodu alındı. Bu değeri canlı .env dosyasında EIDS_USER_CODE alanına yazın.',
            'userCode' => $userCode,
            'envLine' => 'EIDS_USER_CODE='.$userCode,
        ]);
    }
}
