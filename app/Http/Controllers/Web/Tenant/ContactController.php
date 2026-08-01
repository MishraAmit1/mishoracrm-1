<?php

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\ContactRequest;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Quotation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContactController extends Controller
{
    // ── Find contact — tenant scope ke saath ─────────────────────
    private function findContact(int|string $id): Contact
    {
        return Contact::where('id', $id)
            ->where('tenant_id', auth()->user()->tenant_id)
            ->firstOrFail();
    }

    private function tenantId(): int
    {
        return auth()->user()->tenant_id;
    }

    private function tenantSlug(): string
    {
        return auth()->user()->tenant->subdomain;
    }

    // ── Shared filtered query (index page + export reuse this) ────
    public static function filteredQuery(int $tenantId, array $filters): \Illuminate\Database\Eloquent\Builder
    {
        $query = Contact::where('tenant_id', $tenantId);

        if (!empty($filters['search'])) $query->search($filters['search']);
        if (!empty($filters['city']))   $query->where('city', $filters['city']);

        return $query;
    }

    // ── Index ─────────────────────────────────────────────────────
    public function index(Request $request): View
    {
        $query = self::filteredQuery(auth()->user()->tenant_id, $request->all())
            ->with('lead')
            ->withCount(['deals', 'followups', 'invoices']);

        // Sort
        $sort    = $request->get('sort', 'created_at');
        $dir     = $request->get('dir', 'desc');
        $allowed = ['name', 'created_at', 'company', 'city'];
        if (in_array($sort, $allowed)) {
            $query->orderBy($sort, $dir === 'asc' ? 'asc' : 'desc');
        }

        $contacts = $query->paginate(20)->withQueryString();

        $total = Contact::where('tenant_id', auth()->user()->tenant_id)->count();

        return view('tenant.contacts.index', compact('contacts', 'total'));
    }

    // ── Create ────────────────────────────────────────────────────
    public function create(Request $request): View
    {
        // Agar lead_id URL mein hai (lead convert se aaya)
        $lead = $request->filled('lead_id')
            ? Lead::where('id', $request->lead_id)
            ->where('tenant_id', auth()->user()->tenant_id)
            ->first()
            : null;

        $leads = Lead::orderBy('name')->get(['id', 'name', 'phone']);

        return view('tenant.contacts.create', compact('lead', 'leads'));
    }

    // ── Store ─────────────────────────────────────────────────────
    public function store(ContactRequest $request): RedirectResponse
    {
        $data              = $request->validated();
        $data['tenant_id'] = auth()->user()->tenant_id;

        $contact = Contact::create($data);

        return redirect()
            ->route('tenant.contacts.show', [
                'tenant' => $this->tenantSlug(),
                'id'     => $contact->id,
            ])
            ->with('success', "Contact '{$contact->name}' created.");
    }

    // ── Show ──────────────────────────────────────────────────────
    public function show(int|string $id): View
    {
        $contact = $this->findContact($id);
        $contact->load([
            'lead',
            'deals',
            'followups.assignedTo',
            'tasks.assignedTo',
            'quotations',
            'invoices',
        ]);

        return view('tenant.contacts.show', compact('contact'));
    }

    // ── Edit ──────────────────────────────────────────────────────
    public function edit(int|string $id): View
    {
        $contact = $this->findContact($id);
        $leads   = Lead::orderBy('name')->get(['id', 'name', 'phone']);

        return view('tenant.contacts.edit', compact('contact', 'leads'));
    }

    // ── Update ────────────────────────────────────────────────────
    public function update(ContactRequest $request, int|string $id): RedirectResponse
    {
        $contact = $this->findContact($id);
        $contact->update($request->validated());

        return redirect()
            ->route('tenant.contacts.show', [
                'tenant' => $this->tenantSlug(),
                'id'     => $contact->id,
            ])
            ->with('success', 'Contact updated successfully.');
    }

    // ── Destroy ───────────────────────────────────────────────────
    public function destroy(int|string $id): RedirectResponse
    {
        $contact = $this->findContact($id);
        $name    = $contact->name;
        $contact->delete();

        return redirect()
            ->route('tenant.contacts.index', ['tenant' => $this->tenantSlug()])
            ->with('success', "Contact '{$name}' deleted.");
    }

    public function searchCustomers(Request $request): JsonResponse
    {
        $search = $request->get('q');

        $contacts = Contact::where('tenant_id', $this->tenantId())
            ->when($search, function ($q) use ($search) {

                $q->where(function ($sub) use ($search) {

                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('company', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->limit(20)
            ->get([
                'id',
                'name',
                'company',
                'phone',
                'email',
            ]);

        return response()->json($contacts);
    }

    public function customerReport(
        int|string $contact
    ): JsonResponse {

        $contact = Contact::where('tenant_id', $this->tenantId())
            ->findOrFail($contact);

        /*
        |--------------------------------------------------------------------------
        | Quotations
        |--------------------------------------------------------------------------
        */

        $quotations = Quotation::where('tenant_id', $this->tenantId())
            ->where('contact_id', $contact->id)
            ->latest()
            ->limit(5)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Deals
        |--------------------------------------------------------------------------
        */

        $deals = Deal::where('tenant_id', $this->tenantId())
            ->where('contact_id', $contact->id)
            ->latest()
            ->limit(5)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Invoices
        |--------------------------------------------------------------------------
        */

        $invoices = Invoice::where('tenant_id', $this->tenantId())
            ->where('contact_id', $contact->id)
            ->latest()
            ->limit(5)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Summary
        |--------------------------------------------------------------------------
        */

        $totalRevenue = Invoice::where('tenant_id', $this->tenantId())
            ->where('contact_id', $contact->id)
            ->where('status', 'paid')
            ->sum('total');

        $pendingAmount = Invoice::where('tenant_id', $this->tenantId())
            ->where('contact_id', $contact->id)
            ->whereIn('status', ['sent', 'partial', 'overdue'])
            ->sum('total');

        return response()->json([

            'customer' => [

                'id'         => $contact->id,
                'name'       => $contact->name,
                'company'    => $contact->company,
                'phone'      => $contact->phone,
                'email'      => $contact->email,
                'gst_number' => $contact->gst_number,
                'address'    => $contact->address,
                'city'       => $contact->city,
                'state'      => $contact->state,
                'pincode'    => $contact->pincode,

            ],

            'summary' => [

                'quotation_count' => $quotations->count(),
                'deal_count'      => $deals->count(),
                'invoice_count'   => $invoices->count(),
                'total_revenue'   => $totalRevenue,
                'pending_amount'  => $pendingAmount,

            ],

            'quotations' => $quotations,

            'deals' => $deals,

            'invoices' => $invoices,

        ]);
    }
}
