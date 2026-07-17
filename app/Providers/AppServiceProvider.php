<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
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
    }
}
