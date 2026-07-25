<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user?->must_change_password && ! $request->routeIs('temporary-password.*')) {
            return redirect()->route('temporary-password.edit');
        }

        return $next($request);
    }
}
