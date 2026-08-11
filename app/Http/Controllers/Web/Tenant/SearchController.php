<?php

namespace App\Http\Controllers\Web\Tenant;

use App\Helpers\ViewScope;
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

        $leads = $contacts = $deals = collect();

        if ($q !== '') {
            $leads = ViewScope::apply(Lead::where('tenant_id', $tenantId), 'leads', $user)
                ->search($q)
                ->limit(10)
                ->get();

            // Contacts have no assigned_to restriction anywhere in this app —
            // matches ContactController::index() (Contacts deliberately excluded
            // from this permission scoping pass — see ViewScope helper).
            $contacts = Contact::where('tenant_id', $tenantId)
                ->search($q)
                ->limit(10)
                ->get();

            $deals = ViewScope::apply(Deal::where('tenant_id', $tenantId), 'deals', $user)
                ->search($q)
                ->limit(10)
                ->get();
        }

        return view('tenant.search.index', compact('q', 'leads', 'contacts', 'deals'));
    }
}
