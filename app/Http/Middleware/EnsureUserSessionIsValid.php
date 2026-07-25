<?php

namespace App\Http\Middleware;

use App\Models\UserSession;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserSessionIsValid
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user) {
            return $next($request);
        }

        $version = $request->session()->get('auth_session_version');
        $sessionHash = hash('sha256', $request->session()->getId());
        $registered = UserSession::where('user_id', $user->id)->where('session_hash', $sessionHash)->first();
        if (($version !== null && (int) $version !== (int) $user->session_version) || $registered?->revoked_at) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return $request->expectsJson()
                ? response()->json(['message' => 'Your session has been revoked.'], 401)
                : redirect()->route('login')->withErrors(['email' => 'Your session has been revoked. Please sign in again.']);
        }

        if ($registered) {
            $registered->update(['last_seen_at' => now()]);
        }

        return $next($request);
    }
}
