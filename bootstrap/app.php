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
        // Fire-and-forget error reporting to Nalam Pulse Portal
        $exceptions->reportable(function (\Throwable $e) {
            try {
                $cfg = config('services.error_report');
                if (empty($cfg['enabled']) || empty($cfg['url']) || empty($cfg['api_key'])) {
                    return;
                }

                // Build payload
                $user = null;
                try {
                    $authUser = auth()->user();
                    if ($authUser) {
                        $user = [
                            'id'    => $authUser->id,
                            'email' => $authUser->email,
                            'org_id' => $authUser->organization_id ?? null,
                        ];
                    }
                } catch (\Throwable $ignored) {}

                $request = null;
                try {
                    if (app()->runningInConsole()) {
                        $request = ['url' => 'cli:' . implode(' ', $_SERVER['argv'] ?? []), 'method' => 'CLI', 'ip' => '127.0.0.1', 'input' => []];
                    } else {
                        $req = request();
                        $request = [
                            'url'    => $req->fullUrl(),
                            'method' => $req->method(),
                            'ip'     => $req->ip(),
                            'input'  => $req->except(['password', 'password_confirmation', 'token', 'secret', '_token']),
                        ];
                    }
                } catch (\Throwable $ignored) {}

                $payload = json_encode([
                    'level'           => 'error',
                    'message'         => mb_substr($e->getMessage(), 0, 5000),
                    'exception_class' => get_class($e),
                    'file'            => $e->getFile(),
                    'line'            => $e->getLine(),
                    'stack_trace'     => mb_substr($e->getTraceAsString(), 0, 10000),
                    'url'             => $request['url'] ?? null,
                    'method'          => $request['method'] ?? null,
                    'user_info'       => $user,
                    'request_data'    => $request,
                    'environment'     => app()->environment(),
                    'app_version'     => config('app.version', '1.0.0'),
                    'php_version'     => PHP_VERSION,
                ]);

                // Raw cURL — most resilient, avoids framework dependencies
                $ch = curl_init($cfg['url']);
                curl_setopt_array($ch, [
                    CURLOPT_POST           => true,
                    CURLOPT_POSTFIELDS     => $payload,
                    CURLOPT_HTTPHEADER     => [
                        'Content-Type: application/json',
                        'X-Api-Key: ' . $cfg['api_key'],
                    ],
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT        => 2,
                    CURLOPT_CONNECTTIMEOUT => 1,
                    CURLOPT_SSL_VERIFYPEER => true,
                ]);
                curl_exec($ch);
                curl_close($ch);
            } catch (\Throwable $ignored) {
                // Reporter failure must NEVER affect the application
            }
        })->stop(false); // Let Laravel's built-in logger still run
    })->create();
