<?php

namespace App\Http\Middleware;

use App\Enums\AccountStatus;
use App\Enums\UserRole;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MobileDraftAuthentication
{
    public function handle(Request $request, Closure $next)
    {
        $token = (string) $request->bearerToken();
        abort_unless(strlen($token) >= 40 && strlen($token) <= 200, 401, 'Aktarım anahtarı gerekli.');
        $key = DB::table('mobile_draft_tokens')->where('token_hash', hash('sha256', $token))
            ->where('expires_at', '>', now())->first();
        abort_unless($key, 401, 'Aktarım anahtarı geçersiz veya süresi dolmuş.');
        $user = User::find($key->user_id);
        abort_unless($user && $user->account_status === AccountStatus::ACTIVE
            && in_array($user->role, [UserRole::SUPERVISOR, UserRole::AGENT, UserRole::INDIVIDUAL], true), 403);
        Auth::guard('web')->setUser($user);
        $request->setUserResolver(fn () => $user);
        return $next($request);
    }
}
