<?php

use App\Http\Middleware\AuthenticateApiKeyOrToken;
use App\Http\Middleware\CheckApplicationInstalled;
use App\Http\Middleware\EnforceApiFeatureToggles;
use App\Http\Middleware\EnsureCompressedResponse;
use App\Http\Middleware\EnsureMruNotLocked;
use App\Http\Middleware\EnsureSubscriptionNotSuspended;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\RecordApiRequestAnalytics;
use App\Http\Middleware\RestoreAdminIfImpersonating;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

if (! defined('SIGINT')) {
    define('SIGINT', 2);
}
if (! defined('SIGTERM')) {
    define('SIGTERM', 15);
}
if (! defined('SIGHUP')) {
    define('SIGHUP', 1);
}

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(prepend: [
            CheckApplicationInstalled::class,
        ]);

        $middleware->api(append: [
            EnsureCompressedResponse::class,
        ]);

        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
            'active' => EnsureUserIsActive::class,
            'mru.not_locked' => EnsureMruNotLocked::class,
            'subscription.not_suspended' => EnsureSubscriptionNotSuspended::class,
            'admin.restore_impersonation' => RestoreAdminIfImpersonating::class,
            'auth.apikey' => AuthenticateApiKeyOrToken::class,
            'api.analytics' => RecordApiRequestAnalytics::class,
            'api.feature' => EnforceApiFeatureToggles::class,
            'gzip' => EnsureCompressedResponse::class,
            'compress' => EnsureCompressedResponse::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
