<?php

namespace App\Providers;

use App\Models\Deal;
use App\Models\Lead;
use App\Observers\DealObserver;
use App\Observers\LeadObserver;
use Illuminate\Auth\Middleware\RedirectIfAuthenticated;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // App CSS has no Tailwind utilities, so Laravel's default pagination
        // view (Tailwind SVG chevrons) renders unstyled and huge. Bootstrap's
        // markup (.pagination/.page-item/.page-link) is plain text arrows and
        // is styled to match the app's design system in app.css.
        Paginator::useBootstrapFive();

        // The framework's default RedirectIfAuthenticated (used by the
        // 'guest' middleware on /login etc.) redirects already-logged-in
        // users to a route named "dashboard", falling back to "home" (the
        // Laravel welcome page) since this app has no such route. Point it
        // at the real dashboard routes instead.
        RedirectIfAuthenticated::redirectUsing(function ($request) {
            $user = Auth::user();

            if (!$user) {
                return route('home');
            }

            if ($user->isSuperAdmin()) {
                return route('superadmin.dashboard');
            }

            return route('tenant.dashboard', ['tenant' => $user->tenant->subdomain]);
        });

        Lead::observe(LeadObserver::class);
        Deal::observe(DealObserver::class);
    }
}
