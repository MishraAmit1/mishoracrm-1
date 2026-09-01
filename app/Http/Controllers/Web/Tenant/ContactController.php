<?php

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\ContactRequest;
use App\Models\Contact;
use App\Models\ContactAttachment;
use App\Models\ContactEmployee;
use App\Models\ContactEmployeeAttachment;
use App\Models\Deal;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Quotation;
use App\Services\DuplicateMatcher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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

        $this->applyReferral($contact, $request->input('referred_by_code'));
        $this->saveContactAttachments($request, $contact);
        $this->syncEmployees($request, $contact);

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
            'emailLogs.sentBy',
            'whatsappLogs.sentBy',
            'employees.attachments',
            'attachments.uploadedBy',
        ]);

        if (auth()->user()->tenant?->hasModuleEnabled('loyalty')) {
            $contact->load(['loyaltyTransactions' => fn ($q) => $q->limit(20), 'referredBy:id,name']);
        }

        $timeline = \App\Services\ActivityTimelineService::forContact($contact);

        return view('tenant.contacts.show', compact('contact', 'timeline'));
    }

    // ── Edit ──────────────────────────────────────────────────────
    public function edit(int|string $id): View
    {
        $contact = $this->findContact($id);
        $contact->load(['employees.attachments', 'attachments.uploadedBy']);
        $leads   = Lead::orderBy('name')->get(['id', 'name', 'phone']);

        return view('tenant.contacts.edit', compact('contact', 'leads'));
    }

    // ── Update ────────────────────────────────────────────────────
    public function update(ContactRequest $request, int|string $id): RedirectResponse
    {
        $contact = $this->findContact($id);
        $contact->update($request->validated());

        $this->applyReferral($contact, $request->input('referred_by_code'));
        $this->saveContactAttachments($request, $contact);
        $this->syncEmployees($request, $contact);

        return redirect()
            ->route('tenant.contacts.show', [
                'tenant' => $this->tenantSlug(),
                'id'     => $contact->id,
            ])
            ->with('success', 'Contact updated successfully.');
    }

    // ── Referral linkage — first time only, then award both sides ──
    private function applyReferral(Contact $contact, ?string $code): void
    {
        $code = strtoupper(trim((string) $code));

        if ($code === '' || $contact->referred_by_contact_id) {
            return;
        }

        $referrer = Contact::where('tenant_id', $contact->tenant_id)
            ->where('referral_code', $code)
            ->where('id', '!=', $contact->id)
            ->first();

        if (!$referrer) {
            return;
        }

        $contact->referred_by_contact_id = $referrer->id;
        $contact->save();

        app(\App\Services\LoyaltyService::class)->awardReferral($referrer, $contact);
    }

    // ── Contact-level attachments: save uploaded files ─────────────
    private function saveContactAttachments(Request $request, Contact $contact): void
    {
        if (! $request->hasFile('attachments')) {
            return;
        }

        $tenantId = $this->tenantId();

        foreach ($request->file('attachments') as $file) {
            $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
            $path     = $file->storeAs("contacts/{$tenantId}/{$contact->id}", $filename, 'public');

            ContactAttachment::create([
                'tenant_id'     => $tenantId,
                'contact_id'    => $contact->id,
                'uploaded_by'   => auth()->id(),
                'path'          => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type'     => $file->getClientMimeType(),
                'file_size'     => $file->getSize(),
            ]);
        }
    }

    // ── Contact-level attachment: delete ────────────────────────────
    public function destroyAttachment(int|string $id, int|string $attachment): RedirectResponse
    {
        $contact = $this->findContact($id);

        $file = ContactAttachment::where('id', $attachment)
            ->where('contact_id', $contact->id)
            ->firstOrFail();

        Storage::disk('public')->delete($file->path);
        $file->delete();

        return back()->with('success', 'Attachment deleted.');
    }

    // ── Company Employees: create/update/remove from submitted rows ─
    private function syncEmployees(Request $request, Contact $contact): void
    {
        $tenantId       = $this->tenantId();
        $rows           = $request->input('employees', []);
        $indexToEmployee = [];

        foreach ($rows as $index => $row) {
            $name        = trim($row['name'] ?? '');
            $designation = trim($row['designation'] ?? '');
            $emails      = array_values(array_filter(array_map('trim', $row['emails'] ?? [])));
            $phones      = array_values(array_filter(array_map('trim', $row['phones'] ?? [])));
            $files       = $request->file("employees.{$index}.attachments", []);

            // Skip a blank template row (added client-side, left untouched)
            if ($name === '' && $designation === '' && empty($emails) && empty($phones) && empty($files)) {
                continue;
            }

            $employeeId = $row['id'] ?? null;
            $employee   = $employeeId
                ? ContactEmployee::where('id', $employeeId)->where('contact_id', $contact->id)->first()
                : null;

            $data = [
                'tenant_id'   => $tenantId,
                'contact_id'  => $contact->id,
                'name'        => $name,
                'designation' => $designation ?: null,
                'emails'      => $emails,
                'phones'      => $phones,
            ];

            if ($employee) {
                $employee->update($data);
            } else {
                $employee = ContactEmployee::create($data);
            }

            $indexToEmployee[$index] = $employee;

            foreach ($files as $file) {
                $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
                $path     = $file->storeAs("contacts/{$tenantId}/{$contact->id}/employees/{$employee->id}", $filename, 'public');

                ContactEmployeeAttachment::create([
                    'tenant_id'           => $tenantId,
                    'contact_employee_id' => $employee->id,
                    'uploaded_by'         => auth()->id(),
                    'path'                => $path,
                    'original_name'       => $file->getClientOriginalName(),
                    'mime_type'           => $file->getClientMimeType(),
                    'file_size'           => $file->getSize(),
                ]);
            }
        }

        foreach ($request->input('removed_employee_ids', []) as $removeId) {
            ContactEmployee::where('id', $removeId)
                ->where('contact_id', $contact->id)
                ->first()
                ?->delete();
        }

        // ── Primary contact: only one employee per company can be primary ──
        $primaryIndex = $request->input('primary_employee_index');
        if ($primaryIndex !== null && isset($indexToEmployee[$primaryIndex])) {
            ContactEmployee::where('contact_id', $contact->id)->update(['is_primary' => false]);
            $indexToEmployee[$primaryIndex]->update(['is_primary' => true]);
        }
    }

    // ── Employee attachment: delete ─────────────────────────────────
    public function destroyEmployeeAttachment(int|string $employee, int|string $attachment): RedirectResponse
    {
        $employee = ContactEmployee::where('id', $employee)
            ->where('tenant_id', $this->tenantId())
            ->firstOrFail();

        $file = ContactEmployeeAttachment::where('id', $attachment)
            ->where('contact_employee_id', $employee->id)
            ->firstOrFail();

        Storage::disk('public')->delete($file->path);
        $file->delete();

        return back()->with('success', 'Attachment deleted.');
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

    // ── Live duplicate check (Add/Edit forms) ─────────────────────
    public function checkDuplicate(Request $request): JsonResponse
    {
        $match = DuplicateMatcher::findExistingContact(
            $this->tenantId(),
            $request->input('phone'),
            $request->input('email'),
            $request->integer('except_id') ?: null
        );

        return response()->json([
            'duplicate' => (bool) $match,
            'match'     => $match ? ['id' => $match->id, 'name' => $match->name] : null,
        ]);
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
