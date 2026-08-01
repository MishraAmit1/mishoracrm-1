<?php

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\Lead;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SearchController extends Controller
{
    // Backs the topbar search box (see layouts/app.blade.php handleSearch()),
    // which hardcodes /search?q=... — route path must stay exactly /search.
    public function index(Request $request): View
    {
        $q = trim((string) $request->get('q', ''));

        $user     = auth()->user();
        $tenantId = $user->tenant_id;
        $isAdmin  = $user->user_type === 'tenant_admin';

        $leads = $contacts = $deals = collect();

        if ($q !== '') {
            // Same row-visibility rule as LeadController::index() — non-admins
            // only see their own assigned leads, here too.
            $leads = Lead::where('tenant_id', $tenantId)
                ->when(!$isAdmin, fn($query) => $query->where('assigned_to', $user->id))
                ->search($q)
                ->limit(10)
                ->get();

            // Contacts have no assigned_to restriction anywhere in this app —
            // matches ContactController::index().
            $contacts = Contact::where('tenant_id', $tenantId)
                ->search($q)
                ->limit(10)
                ->get();

            // Deals have no forced restriction either — matches DealController::index().
            $deals = Deal::where('tenant_id', $tenantId)
                ->search($q)
                ->limit(10)
                ->get();
        }

        return view('tenant.search.index', compact('q', 'leads', 'contacts', 'deals'));
    }
}
