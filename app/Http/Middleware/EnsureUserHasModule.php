<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasModule
{
    public function handle(Request $request, Closure $next, string $module): Response
    {
        if (! $request->user()?->canAccessModule($module)) {
            abort(403, 'This module is not assigned to your account.');
        }

        return $next($request);
    }
}
