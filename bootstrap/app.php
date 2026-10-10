<?php

use App\Http\Middleware\TrackSiteVisit;
use App\Models\ErrorLog;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            TrackSiteVisit::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'github-deploy-webhook',
            'api/v1/projects/publish',
            'api/v1/mobile-drafts',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->report(function (Throwable $exception): void {
            ErrorLog::recordException($exception);
        });
    })->create();
