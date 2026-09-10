<?php

namespace App\Http\Middleware;

use App\Models\SiteVisit;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackSiteVisit
{
    /**
     * @var array<int, string>
     */
    private array $trackedRoutes = [
        'home',
        'blogs.index',
        'blogs.show',
        'projects.index',
        'projects.show',
        'contact',
        'listings.index',
        'listings.show',
        'legal.show',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($this->shouldTrack($request, $response)) {
            $agent = $request->userAgent() ?? '';

            SiteVisit::query()->create([
                'path' => '/'.ltrim($request->path(), '/'),
                'route_name' => $request->route()?->getName(),
                'method' => $request->method(),
                'status_code' => $response->getStatusCode(),
                'referer' => $this->limit($request->headers->get('referer')),
                'utm_source' => $this->limit($request->query('utm_source')),
                'utm_medium' => $this->limit($request->query('utm_medium')),
                'utm_campaign' => $this->limit($request->query('utm_campaign')),
                'device_type' => $this->detectDevice($agent),
                'browser' => $this->detectBrowser($agent),
                'platform' => $this->detectPlatform($agent),
                'visitor_hash' => $this->visitorHash($request, $agent),
                'user_id' => $request->user()?->id,
                'visited_at' => now(),
            ]);
        }

        return $response;
    }

    private function shouldTrack(Request $request, Response $response): bool
    {
        if (! $request->isMethod('GET') || $request->expectsJson()) {
            return false;
        }

        if ($response->getStatusCode() >= 400) {
            return false;
        }

        return in_array($request->route()?->getName(), $this->trackedRoutes, true);
    }

    private function visitorHash(Request $request, string $agent): string
    {
        return hash_hmac('sha256', implode('|', [
            $request->ip() ?? 'unknown',
            $agent,
        ]), config('app.key'));
    }

    private function detectDevice(string $agent): string
    {
        $agent = strtolower($agent);

        if (str_contains($agent, 'tablet') || str_contains($agent, 'ipad')) {
            return 'Tablet';
        }

        if (str_contains($agent, 'mobile') || str_contains($agent, 'iphone') || str_contains($agent, 'android')) {
            return 'Mobil';
        }

        return 'Masaüstü';
    }

    private function detectBrowser(string $agent): ?string
    {
        return match (true) {
            str_contains($agent, 'Edg/') => 'Microsoft Edge',
            str_contains($agent, 'Chrome/') && ! str_contains($agent, 'Chromium/') => 'Chrome',
            str_contains($agent, 'Safari/') && ! str_contains($agent, 'Chrome/') => 'Safari',
            str_contains($agent, 'Firefox/') => 'Firefox',
            default => null,
        };
    }

    private function detectPlatform(string $agent): ?string
    {
        return match (true) {
            str_contains($agent, 'Windows') => 'Windows',
            str_contains($agent, 'Mac OS X') || str_contains($agent, 'Macintosh') => 'macOS',
            str_contains($agent, 'Android') => 'Android',
            str_contains($agent, 'iPhone') || str_contains($agent, 'iPad') => 'iOS',
            str_contains($agent, 'Linux') => 'Linux',
            default => null,
        };
    }

    private function limit(mixed $value): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        return str($value)->limit(255, '')->toString();
    }
}
