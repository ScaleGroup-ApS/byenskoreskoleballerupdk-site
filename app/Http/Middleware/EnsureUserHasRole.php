<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $userRole = $request->user()?->role;

        if (! $userRole || ! in_array($userRole->value, $roles)) {
            abort(403);
        }

        return $next($request);
    }
}
