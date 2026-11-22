<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class OptionalSanctumAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $header = $request->bearerToken();

        if ($header) {
            $accessToken = PersonalAccessToken::findToken($header);
            if ($accessToken?->tokenable) {
                auth()->setUser($accessToken->tokenable);
                $request->setUserResolver(fn () => $accessToken->tokenable);
            }
        }

        return $next($request);
    }
}
