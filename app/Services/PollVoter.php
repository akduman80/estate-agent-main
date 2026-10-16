<?php

namespace App\Services;

use Illuminate\Http\Request;

class PollVoter
{
    public static function hashes(Request $request, ?string $period = null): array
    {
        $hash = fn (string $value) => hash_hmac('sha256', ($period ? $period.'|' : '').$value, (string) config('app.key'));
        $token = $request->input('voter_token');
        $hashes = [];
        if ($request->user()) {
            $hashes[] = $hash('account:'.$request->user()->getAuthIdentifier());
            if ($period) {
                return $hashes;
            }
        }
        if (is_string($token) && preg_match('/^[a-f0-9-]{36}$/i', $token)) {
            $hashes[] = $hash('browser:'.$token);
        }
        // Preserve recognition of votes recorded before persistent browser IDs.
        if (! $period) {
            $hashes[] = $hash(implode('|', [(string) $request->ip(), (string) $request->userAgent()]));
        }

        return $hashes;
    }
}
