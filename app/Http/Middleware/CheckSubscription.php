<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSubscription
{
    public function handle(Request $request, Closure $next)
    {
        if (!app()->has('tenant')) return $next($request);

        $subscription = app('tenant')->subscription;

        if (!$subscription || $subscription->isExpired()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Subscription expired.'], 402);
            }
            return redirect()->route('tenant.subscription.expired');
        }

        return $next($request);
    }
}
