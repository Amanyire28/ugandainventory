<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'tenant'          => \App\Http\Middleware\EnsureUserBelongsToActiveBusiness::class,
            'role'            => \App\Http\Middleware\CheckRole::class,
            'permission'      => \App\Http\Middleware\CheckPermission::class,
            'subscription'    => \App\Http\Middleware\CheckSubscription::class,
            'log.activity'    => \App\Http\Middleware\LogUserActivity::class,
            'business.active' => \App\Http\Middleware\PreventAccessWhenBusinessInactive::class,
            'owner.only'      => \App\Http\Middleware\CheckOwnerOnly::class,
            'user.activity'   => \App\Http\Middleware\UpdateUserLastActivity::class,
            'auth.admin'      => \App\Http\Middleware\AdminMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
