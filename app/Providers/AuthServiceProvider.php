<?php

namespace App\Providers;

use App\Models\Deal;
use App\Models\Lead;
use App\Models\Quotation;
use App\Models\Task;
use App\Policies\DealPolicy;
use App\Policies\LeadPolicy;
use App\Policies\QuotationPolicy;
use App\Policies\TaskPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::policy(Lead::class, LeadPolicy::class);
        Gate::policy(Deal::class, DealPolicy::class);
        Gate::policy(Task::class, TaskPolicy::class);
        Gate::policy(Quotation::class, QuotationPolicy::class);
    }
}
