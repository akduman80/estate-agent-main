<?php

namespace App\Http\Controllers;

use App\Services\ProjectMapAddresses;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProjectMapAddressController extends Controller
{
    public function __invoke(Request $request, ProjectMapAddresses $addresses): JsonResponse
    {
        $data = $request->validate([
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lon' => ['required', 'numeric', 'between:-180,180'],
        ]);
        $lat = (float) $data['lat'];
        $lon = (float) $data['lon'];
        abort_unless(is_finite($lat) && is_finite($lon), 422);
        try {
            $key = 'project-map-address:v1:'.sprintf('%.7f:%.7f', $lat, $lon);
            $result = Cache::get($key);
            if (! is_array($result)) {
                $result = $addresses->lookup($lat, $lon);
                if (! ($result['partial'] ?? false)) {
                    Cache::put($key, $result, now()->addMinutes(10));
                }
            }
        } catch (Throwable $exception) {
            Log::notice('Project map MAKS lookup failed.', ['exception' => $exception::class]);

            return response()->json(['message' => 'Sokak ve kapı numarası servisine şu anda ulaşılamıyor.'], 503);
        }

        return response()->json([...$result, 'source' => 'TKGM MAKS']);
    }
}
