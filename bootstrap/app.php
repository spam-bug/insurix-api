<?php

declare(strict_types=1);

use App\Http\Middleware\EnsureWorkspaceMember;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    // The frontend authorizes private channels with the agent's session cookie, like any API call.
    ->withBroadcasting(__DIR__.'/../routes/channels.php', ['prefix' => 'api', 'middleware' => ['api', 'auth:sanctum']])
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->statefulApi();
        // The workspace check needs the bound Workspace, so it must run after route model binding,
        // even on routes whose `can` middleware the priority sorting moves bindings for.
        $middleware->appendToPriorityList(SubstituteBindings::class, EnsureWorkspaceMember::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
