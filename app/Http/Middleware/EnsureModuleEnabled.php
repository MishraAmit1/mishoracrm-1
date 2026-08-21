<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

// Blocks a route unless superadmin has enabled the named module for this
// tenant (Tenant.settings['modules'][$module] — see Tenant::hasModuleEnabled()).
// Usage: ->middleware('module:manufacturing')
class EnsureModuleEnabled
{
    public function handle(Request $request, Closure $next, string $module): Response
    {
        $tenant = auth()->user()?->tenant;

        if (!$tenant || !$tenant->hasModuleEnabled($module)) {
            abort(403, ucfirst($module) . ' module is not enabled for your account. Contact support to get access.');
        }

        return $next($request);
    }
}
