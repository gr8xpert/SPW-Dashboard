<?php

use App\Http\Middleware\TenantIsolation;
use App\Http\Middleware\CheckPlanLimits;
use App\Http\Middleware\ApiKeyAuth;
use App\Http\Middleware\RoleAccess;
use App\Http\Middleware\InternalApiAuth;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\WidgetCors;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'tenant'       => TenantIsolation::class,
            'plan.check'   => CheckPlanLimits::class,
            'api.key'      => ApiKeyAuth::class,
            'role'         => RoleAccess::class,
            'internal.api' => InternalApiAuth::class,
            'widget.cors'  => WidgetCors::class,
        ]);

        $middleware->web(append: [
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
            SecurityHeaders::class,
        ]);

        $middleware->api(append: [
            SecurityHeaders::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'api/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
