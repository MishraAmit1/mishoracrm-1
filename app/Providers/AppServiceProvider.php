<?php

namespace App\Providers;

use App\Models\Deal;
use App\Models\Lead;
use App\Observers\DealObserver;
use App\Observers\LeadObserver;
use Illuminate\Auth\Middleware\RedirectIfAuthenticated;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
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
        // Production sits behind a reverse proxy that terminates SSL and
        // forwards plain HTTP to the app, so $request->getScheme() (and
        // every url()/route() call built from it — including the Meta
        // OAuth/webhook callback URLs shown in Superadmin > Platform
        // Settings) reports "http" even though the browser used https.
        // Force https whenever APP_URL is configured as https, regardless
        // of what scheme the request actually arrived on.
        if (str_starts_with(config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        // App CSS has no Tailwind utilities, so Laravel's default pagination
        // view (Tailwind SVG chevrons) renders unstyled and huge. Bootstrap's
        // markup (.pagination/.page-item/.page-link) is plain text arrows and
        // is styled to match the app's design system in app.css.
        Paginator::useBootstrapFive();

        // Laravel's own bootstrap-5 view renders every page number with no
        // ellipsis whenever the paginator has fewer than ~14 pages, which
        // looks broken (e.g. 1..12 all as buttons). Use the same markup/CSS
        // classes but always window to current page ± 2 with first/last
        // jump links, matching the pagination style used elsewhere in the app.
        Paginator::defaultView('vendor.pagination.app');

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
