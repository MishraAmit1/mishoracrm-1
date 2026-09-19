<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSubscription
{
    public function handle(Request $request, Closure $next)
    {
        // On the single shared domain the host never identifies the tenant, so
        // `app('tenant')` is unbound — fall back to the logged-in user's tenant.
        // Superadmins have no tenant and are not subscription-gated.
        $tenant = app()->has('tenant') ? app('tenant') : $request->user()?->tenant;

        if (!$tenant) return $next($request);

        $subscription = $tenant->subscription;

        if (!$subscription || $subscription->isExpired()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Subscription expired.'], 402);
            }
            return redirect()->route('tenant.subscription.expired');
        }

        return $next($request);
    }
}
