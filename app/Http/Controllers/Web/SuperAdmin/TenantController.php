<?php

namespace App\Http\Controllers\Web\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TenantController extends Controller
{
    public function index(Request $request): View
    {
        $query = Tenant::with(['subscription.plan'])
            ->withCount(['users as user_count' => fn($q) => $q->withoutGlobalScopes()]);

        if ($request->filled('search')) {
            $query->where(fn($q) => $q
                ->where('name', 'like', "%{$request->search}%")
                ->orWhere('email', 'like', "%{$request->search}%")
                ->orWhere('subdomain', 'like', "%{$request->search}%")
            );
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $tenants = $query->latest()->paginate(15)->withQueryString();

        // Fetch tenant admin last_login_at for each tenant
        $tenantIds  = $tenants->pluck('id')->toArray();
        $lastLogins = User::withoutGlobalScopes()
            ->whereIn('tenant_id', $tenantIds)
            ->where('user_type', 'tenant_admin')
            ->select('tenant_id', 'last_login_at')
            ->get()
            ->keyBy('tenant_id');

        $stats = [
            'total'    => Tenant::count(),
            'active'   => Tenant::where('status', 'active')->count(),
            'inactive' => Tenant::where('status', 'inactive')->count(),
            'suspended'=> Tenant::where('status', 'suspended')->count(),
        ];

        return view('superadmin.tenants.index', compact('tenants', 'lastLogins', 'stats'));
    }

    public function show(Tenant $tenant): View
    {
        $tenant->load(['subscription.plan', 'subscription.coupon']);

        // Full subscription / payment history
        $paymentHistory = Subscription::with(['plan', 'coupon'])
            ->where('tenant_id', $tenant->id)
            ->latest()
            ->get();

        // Users of this tenant
        $users = User::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->with('roles')
            ->orderByRaw("FIELD(user_type, 'tenant_admin', 'staff')")
            ->orderByDesc('last_login_at')
            ->get();

        $userCount     = $users->count();
        $planUserLimit = $tenant->subscription?->plan
            ? (int) ($tenant->subscription->plan->features['users'] ?? 0)
            : null;

        return view('superadmin.tenants.show', compact(
            'tenant',
            'paymentHistory',
            'users',
            'userCount',
            'planUserLimit'
        ));
    }

    public function toggleStatus(Tenant $tenant, Request $request): RedirectResponse
    {
        $request->validate(['status' => ['required', 'in:active,inactive,suspended']]);

        $old = $tenant->status;
        $tenant->update(['status' => $request->status]);

        return back()->with('success', "Tenant status changed from {$old} to {$request->status}.");
    }
}
