<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

// Allows only a tenant's admin(s) through. Checks the `user_type` column
// (kept in sync with the assigned role everywhere), so it also covers admins
// on a customised "tenant_{id}_admin" copy of the shared tenant_admin role.
//
// Usage: ->middleware('tenant.admin')   (replaces the old 'role:tenant_admin')
class EnsureTenantAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->user_type !== 'tenant_admin') {
            abort(403, 'This area is restricted to workspace admins.');
        }

        return $next($request);
    }
}
