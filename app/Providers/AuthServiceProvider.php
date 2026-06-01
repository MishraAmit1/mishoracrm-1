<?php

namespace App\Providers;

use App\Models\Lead;
use App\Policies\LeadPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::policy(Lead::class, LeadPolicy::class);
    }
}
