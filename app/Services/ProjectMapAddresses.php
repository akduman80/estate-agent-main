<?php

namespace App\Services;

use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class ProjectMapAddresses
{
    private const ENDPOINT = 'https://cbsapi.tkgm.gov.tr/megsiswebapi.v3.1/api/maksIdariYapi/';

    public function lookup(float $lat, float $lon): array
    {
        $parent = null;
        foreach (['illiste', 'ilceliste', 'mahalleliste'] as $level) {
            $path = $level.($parent ? '/'.rawurlencode($parent) : '');
            $region = collect($this->features($path))->first(fn ($feature) => $this->contains($feature['geometry'] ?? [], $lat, $lon));
            $parent = $region['properties']['id'] ?? null;
            if (! $parent) {
                return ['street' => null, 'candidates' => [], 'streets' => []];
            }
            $this->validateId($parent);
        }

        $roads = [];
        foreach ($this->features('yolliste/'.rawurlencode($parent)) as $feature) {
            $name = trim((string) ($feature['properties']['text'] ?? ''));
            $id = (string) ($feature['properties']['id'] ?? '');
            if ($name === '' || $id === '') {
                continue;
            }
            $point = $this->nearestOnGeometry($feature['geometry']['coordinates'] ?? [], $lat, $lon);
            if ($point && $point['distance'] <= 120) {
                $this->validateId($id);
                $roads[] = ['id' => $id, 'name' => $name, ...$point];
            }
        }
        usort($roads, fn ($a, $b) => $a['distance'] <=> $b['distance']);
        $roads = array_slice($roads, 0, 6);
        $missing = [];
        $doorLists = [];
        foreach ($roads as $road) {
            $path = 'numaratajliste/'.rawurlencode($road['id']);
            $cached = Cache::get($this->cacheKey($path));
            if (is_array($cached)) {
                $doorLists[$road['id']] = $cached;
            } else {
                $missing[$road['id']] = $path;
            }
        }
        $replies = Http::pool(function (Pool $pool) use ($missing): array {
            $requests = [];
            foreach ($missing as $id => $path) {
                $requests[] = $pool->as($id)->acceptJson()->connectTimeout(3)->timeout(6)
                    ->withHeaders(['Referer' => 'https://parselsorgu.tkgm.gov.tr/'])
                    ->withOptions(['allow_redirects' => false])->get(self::ENDPOINT.$path);
            }

            return $requests;
        });
        $unavailable = false;
        foreach ($missing as $id => $path) {
            $reply = $replies[$id] ?? null;
            if (! $reply instanceof Response || ! $reply->successful() || ! is_array($reply->json('features'))) {
                $unavailable = true;

                continue;
            }
            $doorLists[$id] = $reply->json('features');
            Cache::put($this->cacheKey($path), $doorLists[$id], now()->addDay());
        }

        $candidates = [];
        foreach ($roads as $road) {
            foreach ($doorLists[$road['id']] ?? [] as $feature) {
                $geometry = $feature['geometry'] ?? [];
                $coordinates = $geometry['coordinates'] ?? [];
                if (($geometry['type'] ?? '') !== 'Point' || ! $this->isPoint($coordinates)) {
                    continue;
                }
                $door = trim(preg_replace('/\s*\([^)]*\)\s*$/u', '', (string) ($feature['properties']['text'] ?? '')));
                $distance = $this->meters($lat, $lon, (float) $coordinates[1], (float) $coordinates[0]);
                if ($door !== '' && $distance <= 120) {
                    $key = $road['id'].'|'.$door.'|'.implode(',', $coordinates);
                    $candidates[$key] = ['street' => $road['name'], 'door' => mb_substr($door, 0, 100),
                        'latitude' => (float) $coordinates[1], 'longitude' => (float) $coordinates[0], 'distance' => round($distance, 1)];
                }
            }
        }
        usort($candidates, fn ($a, $b) => $a['distance'] <=> $b['distance']);

        return ['street' => $roads[0]['name'] ?? null, 'streets' => array_map(fn ($road) => [
            'name' => $road['name'], 'latitude' => $road['latitude'], 'longitude' => $road['longitude'],
        ], $roads), 'candidates' => array_slice($candidates, 0, 60), 'partial' => $unavailable];
    }

    private function features(string $path): array
    {
        return Cache::remember($this->cacheKey($path), now()->addDay(), function () use ($path): array {
            $reply = Http::acceptJson()->connectTimeout(3)->timeout(6)
                ->withHeaders(['Referer' => 'https://parselsorgu.tkgm.gov.tr/'])
                ->withOptions(['allow_redirects' => false])->get(self::ENDPOINT.$path)->throw();
            $features = $reply->json('features');
            if (! is_array($features)) {
                throw new RuntimeException('Invalid MAKS response');
            }

            return $features;
        });
    }

    private function cacheKey(string $path): string
    {
        return 'project-map-maks:v1:'.hash('sha256', $path);
    }

    private function validateId(string $id): void
    {
        if (! preg_match('/^(?:\d{1,12}|\{?[a-fA-F0-9-]{36}\}?)$/', $id)) {
            throw new RuntimeException('Invalid MAKS identifier');
        }
    }

    private function isPoint(array $point): bool
    {
        return count($point) >= 2 && is_numeric($point[0] ?? null) && is_numeric($point[1] ?? null)
            && is_finite((float) $point[0]) && is_finite((float) $point[1])
            && abs((float) $point[0]) <= 180 && abs((float) $point[1]) <= 90;
    }

    private function contains(array $geometry, float $lat, float $lon): bool
    {
        $polygons = match ($geometry['type'] ?? '') {
            'Polygon' => [$geometry['coordinates'] ?? []],
            'MultiPolygon' => $geometry['coordinates'] ?? [],
            default => [],
        };
        foreach ($polygons as $polygon) {
            if (! $this->insideRing($polygon[0] ?? [], $lat, $lon)) {
                continue;
            }
            $inHole = false;
            foreach (array_slice($polygon, 1) as $hole) {
                $inHole = $inHole || $this->insideRing($hole, $lat, $lon);
            }
            if (! $inHole) {
                return true;
            }
        }

        return false;
    }

    private function insideRing(array $ring, float $lat, float $lon): bool
    {
        $inside = false;
        $count = count($ring);
        for ($i = 0, $j = $count - 1; $i < $count; $j = $i++) {
            $a = $ring[$i];
            $b = $ring[$j];
            if (! is_array($a) || ! is_array($b) || ! $this->isPoint($a) || ! $this->isPoint($b)) {
                return false;
            }
            if (($a[1] > $lat) !== ($b[1] > $lat) && $lon < ($b[0] - $a[0]) * ($lat - $a[1]) / ($b[1] - $a[1]) + $a[0]) {
                $inside = ! $inside;
            }
        }

        return $inside;
    }

    private function nearestOnGeometry(array $coordinates, float $lat, float $lon): ?array
    {
        $best = null;
        $previous = null;
        foreach ($coordinates as $part) {
            if (! is_array($part)) {
                continue;
            }
            if ($this->isPoint($part)) {
                $point = $this->onSegment($lat, $lon, $previous ?? $part, $part);
                $previous = $part;
            } else {
                $point = $this->nearestOnGeometry($part, $lat, $lon);
                $previous = null;
            }
            if ($point && (! $best || $point['distance'] < $best['distance'])) {
                $best = $point;
            }
        }

        return $best;
    }

    private function onSegment(float $lat, float $lon, array $a, array $b): array
    {
        $scale = 111320 * cos(deg2rad($lat));
        $dx = ($b[0] - $a[0]) * $scale;
        $dy = ($b[1] - $a[1]) * 111320;
        $length = $dx * $dx + $dy * $dy;
        $fraction = $length > 0 ? max(0, min(1, (($lon - $a[0]) * $scale * $dx + ($lat - $a[1]) * 111320 * $dy) / $length)) : 0;
        $pointLat = $a[1] + $fraction * ($b[1] - $a[1]);
        $pointLon = $a[0] + $fraction * ($b[0] - $a[0]);

        return ['latitude' => $pointLat, 'longitude' => $pointLon, 'distance' => $this->meters($lat, $lon, $pointLat, $pointLon)];
    }

    private function meters(float $lat, float $lon, float $otherLat, float $otherLon): float
    {
        return hypot(($otherLat - $lat) * 111320, ($otherLon - $lon) * 111320 * cos(deg2rad($lat)));
    }
}
