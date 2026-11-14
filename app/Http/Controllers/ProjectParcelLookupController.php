<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProjectParcelLookupController extends Controller
{
    private const ENDPOINT = 'https://cbsapi.tkgm.gov.tr/megsiswebapi.v3.1/api/parsel/';

    public function __invoke(Request $request): JsonResponse
    {
        $coordinates = $request->validate([
            'lat' => ['required', 'regex:/^-?\d{1,3}(?:\.\d{1,8})?$/'],
            'lon' => ['required', 'regex:/^-?\d{1,3}(?:\.\d{1,8})?$/'],
        ]);
        $lat = (float) $coordinates['lat'];
        $lon = (float) $coordinates['lon'];

        abort_unless(is_finite($lat) && $lat >= -90 && $lat <= 90 && is_finite($lon) && $lon >= -180 && $lon <= 180, 422);

        $cacheKey = 'project-parcel:'.number_format($lat, 8, '.', '').':'.number_format($lon, 8, '.', '');
        $cached = Cache::get($cacheKey);
        if (is_array($cached)) {
            return response()->json($cached);
        }

        try {
            $response = Http::acceptJson()
                ->withHeaders(['Referer' => 'https://parselsorgu.tkgm.gov.tr/'])
                ->connectTimeout(4)
                ->timeout(8)
                ->withOptions(['allow_redirects' => false])
                ->get(self::ENDPOINT.rawurlencode((string) $lat).'/'.rawurlencode((string) $lon).'/');
        } catch (Throwable $exception) {
            Log::notice('TKGM parcel lookup failed.', ['exception' => $exception::class]);

            return response()->json(['found' => false, 'message' => 'Kadastro servisine şu anda ulaşılamıyor.'], 503);
        }

        if ($response->status() === 404 || $response->status() === 204) {
            $result = $this->notFound();
            Cache::put($cacheKey, $result, now()->addSeconds(60));

            return response()->json($result);
        }

        if (! $response->successful()) {
            Log::notice('TKGM parcel lookup returned an unsuccessful status.', ['status' => $response->status()]);

            return response()->json(['found' => false, 'message' => 'Kadastro servisi geçerli bir yanıt vermedi.'], 502);
        }

        $payload = $response->json();
        if (! is_array($payload)) {
            return response()->json(['found' => false, 'message' => 'Kadastro servisinin yanıtı okunamadı.'], 502);
        }

        $feature = $payload;
        if (isset($payload['features'][0]) && is_array($payload['features'][0])) {
            $feature = $payload['features'][0];
        } elseif (isset($payload['data']) && is_array($payload['data'])) {
            $feature = $payload['data'];
        }

        $properties = $feature['properties'] ?? null;
        if (! is_array($properties) || ! $this->hasParcel($properties)) {
            $result = $this->notFound();
            Cache::put($cacheKey, $result, now()->addSeconds(60));

            return response()->json($result);
        }

        $parcel = [];
        foreach ([
            'ilAd' => 'province',
            'ilceAd' => 'district',
            'mahalleAd' => 'neighborhood',
            'adaNo' => 'block',
            'parselNo' => 'parcel',
            'alan' => 'area',
            'nitelik' => 'quality',
            'pafta' => 'sheet',
            'mevkii' => 'locality',
        ] as $source => $target) {
            $value = $properties[$source] ?? null;
            $parcel[$target] = is_scalar($value) && trim((string) $value) !== '' ? mb_substr(trim((string) $value), 0, 160) : null;
        }

        $geometry = $feature['geometry'] ?? $payload['geometry'] ?? null;
        if (! $this->validGeometry($geometry)) {
            $geometry = null;
        }

        $result = ['found' => true, 'message' => null, 'parcel' => $parcel, 'geometry' => $geometry];
        Cache::put($cacheKey, $result, now()->addSeconds(60));

        return response()->json($result);
    }

    private function hasParcel(array $properties): bool
    {
        foreach (['adaNo', 'parselNo', 'ilAd', 'ilceAd', 'mahalleAd'] as $key) {
            if (isset($properties[$key]) && is_scalar($properties[$key]) && trim((string) $properties[$key]) !== '') {
                return true;
            }
        }

        return false;
    }

    private function notFound(): array
    {
        return ['found' => false, 'message' => 'Bu noktada parsel bulunamadı.', 'parcel' => null, 'geometry' => null];
    }

    private function validGeometry(mixed $geometry): bool
    {
        if (! is_array($geometry) || ! in_array($geometry['type'] ?? null, ['Polygon', 'MultiPolygon'], true) || ! is_array($geometry['coordinates'] ?? null)) {
            return false;
        }

        $count = 0;
        $walk = function (mixed $node, int $depth = 0) use (&$walk, &$count): bool {
            if (! is_array($node) || $depth > 5) {
                return false;
            }
            if (array_is_list($node) && count($node) >= 2 && is_numeric($node[0] ?? null) && is_numeric($node[1] ?? null)) {
                $lon = (float) $node[0];
                $lat = (float) $node[1];
                $count++;

                return $count <= 10000 && is_finite($lon) && is_finite($lat) && $lon >= -180 && $lon <= 180 && $lat >= -90 && $lat <= 90;
            }
            if (! array_is_list($node) || $node === []) {
                return false;
            }
            foreach ($node as $child) {
                if (! $walk($child, $depth + 1)) {
                    return false;
                }
            }

            return true;
        };

        $minimumDepth = $geometry['type'] === 'Polygon' ? 2 : 3;
        $first = $geometry['coordinates'];
        $depth = 0;
        while (is_array($first) && $first !== [] && array_is_list($first)) {
            if (is_numeric($first[0] ?? null) && is_numeric($first[1] ?? null)) {
                break;
            }
            $first = $first[0];
            $depth++;
        }

        return $depth >= $minimumDepth && $walk($geometry['coordinates']);
    }
}
