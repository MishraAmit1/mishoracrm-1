<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

// Guards the customer portal. Deliberately NOT Laravel's `auth:customer`:
// that calls Auth::shouldUse('customer'), making the Customer the *default*
// guard for the whole request — so auth()->user() would be a Customer inside
// BelongsToTenant's scope and AuditLog::record(), which both assume a tenant
// User (isSuperAdmin() crash, customer id stored as audit user_id). Keeping the
// default guard untouched leaves all that shared code behaving as it does for
// any other unauthenticated request.
class AuthenticateCustomer
{
    public function handle(Request $request, Closure $next): Response
    {
        $guard = Auth::guard('customer');

        if (!$guard->check()) {
            return $request->expectsJson()
                ? abort(401)
                : redirect()->route('portal.login');
        }

        // A customer blocked after logging in loses access immediately
        // instead of riding out their remember-me cookie.
        if ($guard->user()->isBlocked()) {
            $guard->logout();

            abort(403, 'This account has been suspended.');
        }

        return $next($request);
    }
}
