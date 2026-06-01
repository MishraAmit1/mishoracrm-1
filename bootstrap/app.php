<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {

        $middleware->statefulApi();

        // Webhook routes CSRF se exempt — each has its own signature/token verification
        $middleware->validateCsrfTokens(except: [
            'webhook/razorpay',
            'webhook/instagram',
            'webhook/whatsapp',
            'webhook/leads/*',   // Meta Lead Ads, JustDial, TradeIndia, Sulekha
        ]);

        $middleware->alias([
            // Custom
            'tenant'       => \App\Http\Middleware\IdentifyTenant::class,
            'subscription' => \App\Http\Middleware\CheckSubscription::class,

            // Spatie — yeh teeno register karne zaroori hain
            'role'               => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission'         => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);

    })
    ->withExceptions(function (Exceptions $exceptions) {

        $exceptions->render(function (\Throwable $e, $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                $status = method_exists($e, 'getStatusCode')
                    ? $e->getStatusCode()
                    : 500;

                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], $status);
            }
        });

    })
    ->create();