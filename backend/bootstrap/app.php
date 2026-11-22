<?php

use App\Http\Middleware\EnsurePermission;
use App\Http\Middleware\EnsureStaff;
use App\Http\Middleware\EnsureStaffWeb;
use App\Http\Middleware\OptionalSanctumAuth;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\HandleCors;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Cloudflare / reverse-proxy tunnels terminate TLS; trust X-Forwarded-* so
        // Secure cookies + CSRF + URL generation work over HTTPS demos.
        $middleware->trustProxies(at: '*');

        $middleware->append(SecurityHeaders::class);
        $middleware->append(HandleCors::class);

        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'staff' => EnsureStaff::class,
            'staff.web' => EnsureStaffWeb::class,
            'perm' => EnsurePermission::class,
            'auth.optional' => OptionalSanctumAuth::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
