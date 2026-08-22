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

    // ── Module access — Manufacturing (Work Orders + Product Batches) ──
    // Sets an explicit force-on/force-off override for this tenant,
    // regardless of what their Plan includes (see Tenant::hasModuleEnabled()).
    public function toggleManufacturing(Tenant $tenant, Request $request): RedirectResponse
    {
        $request->validate(['enabled' => ['required', 'boolean']]);

        $settings = $tenant->settings ?? [];
        $settings['modules']['manufacturing'] = $request->boolean('enabled');
        $tenant->update(['settings' => $settings]);

        $state = $request->boolean('enabled') ? 'enabled' : 'disabled';

        return back()->with('success', "Manufacturing module {$state} for {$tenant->name}.");
    }

    // Removes the manual override, so access reverts to whatever the
    // tenant's current Plan dictates.
    public function clearManufacturingOverride(Tenant $tenant): RedirectResponse
    {
        $settings = $tenant->settings ?? [];
        unset($settings['modules']['manufacturing']);
        $tenant->update(['settings' => $settings]);

        return back()->with('success', "Manufacturing access for {$tenant->name} now follows their plan.");
    }

    // ── Module access — Service (Service Catalog for service-based tenants) ──
    public function toggleService(Tenant $tenant, Request $request): RedirectResponse
    {
        $request->validate(['enabled' => ['required', 'boolean']]);

        $settings = $tenant->settings ?? [];
        $settings['modules']['service'] = $request->boolean('enabled');
        $tenant->update(['settings' => $settings]);

        $state = $request->boolean('enabled') ? 'enabled' : 'disabled';

        return back()->with('success', "Service module {$state} for {$tenant->name}.");
    }

    public function clearServiceOverride(Tenant $tenant): RedirectResponse
    {
        $settings = $tenant->settings ?? [];
        unset($settings['modules']['service']);
        $tenant->update(['settings' => $settings]);

        return back()->with('success', "Service access for {$tenant->name} now follows their plan.");
    }

    // ── Module access — generic (Subscriptions, Appointments, Time
    // Tracking, and any future module) so each new sub-feature doesn't
    // need its own copy-pasted toggle/clear method pair like
    // manufacturing/service above did. Same tri-state override behavior. ──
    private const TOGGLEABLE_MODULES = ['subscriptions', 'appointments', 'time_tracking', 'tickets'];

    private function moduleLabel(string $module): string
    {
        return match ($module) {
            'subscriptions' => 'Service Subscriptions',
            'appointments'  => 'Appointments / Booking',
            'time_tracking' => 'Time Tracking',
            'tickets'       => 'Tickets / Helpdesk',
            default         => ucfirst(str_replace('_', ' ', $module)),
        };
    }

    public function toggleModule(Tenant $tenant, string $module, Request $request): RedirectResponse
    {
        abort_unless(in_array($module, self::TOGGLEABLE_MODULES, true), 404);
        $request->validate(['enabled' => ['required', 'boolean']]);

        $settings = $tenant->settings ?? [];
        $settings['modules'][$module] = $request->boolean('enabled');
        $tenant->update(['settings' => $settings]);

        $state = $request->boolean('enabled') ? 'enabled' : 'disabled';

        return back()->with('success', $this->moduleLabel($module) . " {$state} for {$tenant->name}.");
    }

    public function clearModuleOverride(Tenant $tenant, string $module): RedirectResponse
    {
        abort_unless(in_array($module, self::TOGGLEABLE_MODULES, true), 404);

        $settings = $tenant->settings ?? [];
        unset($settings['modules'][$module]);
        $tenant->update(['settings' => $settings]);

        return back()->with('success', $this->moduleLabel($module) . " access for {$tenant->name} now follows their plan.");
    }
}
