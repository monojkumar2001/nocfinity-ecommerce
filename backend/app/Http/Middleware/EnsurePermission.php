<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Permission check against the already-authenticated request user
 * (works with auth:admin session guard + web Spatie permissions).
 */
class EnsurePermission
{
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = $request->user();

        if (!$user) {
            abort(403, 'Unauthorized.');
        }

        foreach ($permissions as $permission) {
            $needed = array_filter(explode('|', $permission));
            if ($needed && !$user->canAny($needed)) {
                abort(403, 'Missing permission: ' . implode(' or ', $needed));
            }
        }

        return $next($request);
    }
}
