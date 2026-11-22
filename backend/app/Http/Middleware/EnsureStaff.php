<?php

namespace App\Http\Middleware;

use App\Http\Responses\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStaff
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || !$user->isStaff()) {
            return ApiResponse::error('Unauthorized.', 403);
        }

        // Prefer admin-scoped Sanctum tokens from /admin/login.
        // Session/transient tokens (SPA / tests) still pass via tokenCan().
        if (!$user->tokenCan('admin')) {
            return ApiResponse::error('Admin token required. Sign in via /admin/login.', 403);
        }

        return $next($request);
    }
}
