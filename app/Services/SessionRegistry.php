<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserSession;
use Illuminate\Http\Request;

class SessionRegistry
{
    public function register(Request $request, User $user): void
    {
        $request->session()->put('auth_session_version', $user->session_version ?: 1);
        UserSession::updateOrCreate(
            ['session_hash' => hash('sha256', $request->session()->getId())],
            [
                'user_id' => $user->id,
                'ip_address' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 1000),
                'last_seen_at' => now(),
                'revoked_at' => null,
            ],
        );
    }

    public function revokeCurrent(Request $request): void
    {
        if ($request->hasSession()) {
            UserSession::where('session_hash', hash('sha256', $request->session()->getId()))
                ->update(['revoked_at' => now()]);
        }
    }
}
