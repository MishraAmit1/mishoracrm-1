<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IdentifyTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        $host       = $request->getHost();
        $baseDomain = config('app.base_domain'); // saas-crm.test

        // ── 1. Skip base domain (superadmin) ───────────────────────
        if ($host === $baseDomain) {
            return $next($request);
        }

        // ── 2. Skip localhost / IP ────────────────────────────────
        if (
            $host === 'localhost' ||
            filter_var($host, FILTER_VALIDATE_IP) ||
            str_starts_with($host, '127.0.0.1')
        ) {
            return $next($request);
        }

        // ── 3. Extract subdomain ──────────────────────────────────
        // demo.saas-crm.test → demo
        if (!str_ends_with($host, '.' . $baseDomain)) {
            return $next($request); // safety
        }

        $subdomain = str_replace('.' . $baseDomain, '', $host);

        if (empty($subdomain)) {
            return $next($request);
        }

        // ── 4. Find tenant (cached) ───────────────────────────────
        $tenant = cache()->remember(
            "tenant_{$subdomain}",
            now()->addMinutes(10),
            fn () => Tenant::where('subdomain', $subdomain)
                ->where('status', 'active')
                ->first()
        );

        // ── 5. Not found ──────────────────────────────────────────
        if (!$tenant) {
            abort(404, 'Workspace not found.');
        }

        // ── 6. Bind tenant globally ───────────────────────────────
        app()->instance('tenant', $tenant);
        app()->instance('tenant_id', $tenant->id);

        // Optional: timezone
        config([
            'app.timezone' => $tenant->timezone ?? 'Asia/Kolkata'
        ]);

        return $next($request);
    }
}