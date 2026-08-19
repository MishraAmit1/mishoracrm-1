<?php

namespace App\Providers;

use App\Models\Deal;
use App\Models\Followup;
use App\Models\Lead;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\Quotation;
use App\Models\Task;
use App\Models\Vendor;
use App\Models\WorkOrder;
use App\Policies\DealPolicy;
use App\Policies\FollowupPolicy;
use App\Policies\LeadPolicy;
use App\Policies\PurchaseOrderPolicy;
use App\Policies\PurchaseRequestPolicy;
use App\Policies\QuotationPolicy;
use App\Policies\TaskPolicy;
use App\Policies\VendorPolicy;
use App\Policies\WorkOrderPolicy;
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
        Gate::policy(Followup::class, FollowupPolicy::class);
        Gate::policy(Vendor::class, VendorPolicy::class);
        Gate::policy(PurchaseRequest::class, PurchaseRequestPolicy::class);
        Gate::policy(PurchaseOrder::class, PurchaseOrderPolicy::class);
        Gate::policy(WorkOrder::class, WorkOrderPolicy::class);
    }
}
