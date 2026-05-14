<?php

namespace App\Http\Controllers\Web\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeadController extends Controller
{
    // All tenants ke leads — superadmin dekhta hai
    public function index(Request $request): View
    {
        $query = Lead::withoutGlobalScopes()
            ->with(['tenant', 'assignedTo'])
            ->latest();

        // Filter by tenant
        if ($request->filled('tenant_id')) {
            $query->where('tenant_id', $request->tenant_id);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by source
        if ($request->filled('source')) {
            $query->where('source', $request->source);
        }

        // Search
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name',    'like', "%{$request->search}%")
                  ->orWhere('phone', 'like', "%{$request->search}%")
                  ->orWhere('email', 'like', "%{$request->search}%");
            });
        }

        $leads    = $query->paginate(20)->withQueryString();
        $tenants  = Tenant::orderBy('name')->get(['id', 'name']);
        $statuses = Lead::statuses();
        $sources  = Lead::sources();

        // Summary stats across all tenants
        $stats = [
            'total'     => Lead::withoutGlobalScopes()->count(),
            'new'       => Lead::withoutGlobalScopes()->where('status', 'new')->count(),
            'converted' => Lead::withoutGlobalScopes()->where('status', 'converted')->count(),
            'lost'      => Lead::withoutGlobalScopes()->where('status', 'lost')->count(),
        ];

        return view('superadmin.leads.index', compact(
            'leads', 'tenants', 'statuses', 'sources', 'stats'
        ));
    }

    // Lead detail — superadmin view
    public function show(Lead $lead): View
    {
        $lead->loadWithoutGlobalScopes([
            'assignedTo', 'createdBy', 'tenant', 'followups', 'tasks'
        ]);

        return view('superadmin.leads.show', compact('lead'));
    }

    // Delete — superadmin force delete kar sakta hai
    public function destroy(Lead $lead)
    {
        $lead->withoutGlobalScopes()->delete();

        return redirect()
            ->route('superadmin.leads.index')
            ->with('success', 'Lead deleted.');
    }
}