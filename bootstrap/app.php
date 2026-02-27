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
        // Trust all reverse proxies (ngrok, Cloudflare, load balancers, etc.)
        // so that X-Forwarded-Proto / X-Forwarded-Host are respected.
        $middleware->trustProxies(at: '*');

        // Dynamically adapt asset/route URLs to match the proxied scheme+host.
        $middleware->prepend(\App\Http\Middleware\AdaptUrlToProxy::class);

        $middleware->alias([
            'role' => \App\Http\Middleware\CheckRole::class,
            'ensure.org' => \App\Http\Middleware\EnsureOrganization::class,
            'premium' => \App\Http\Middleware\CheckPremium::class,
        ]);

        // Webhooks use signature-based auth — no CSRF needed
        $middleware->validateCsrfTokens(except: [
            'webhooks/stripe',
            'webhooks/razorpay',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
