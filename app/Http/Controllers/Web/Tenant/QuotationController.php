<?php

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\QuotationRequest;
use App\Models\Contact;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Product;
use App\Models\Quotation;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QuotationController extends Controller
{
    // ── Find quotation — tenant scope ─────────────────────────────
    private function findQuotation(int|string $id): Quotation
    {
        return Quotation::where('id', $id)
            ->where('tenant_id', auth()->user()->tenant_id)
            ->firstOrFail();
    }

    // ── Index ─────────────────────────────────────────────────────
    public function index(Request $request): View
    {
        $query = Quotation::with(['contact', 'lead', 'createdBy'])
            ->latest();

        if ($request->filled('status')) {
            $query->status($request->status);
        }

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('number', 'like', "%{$request->search}%")
                    ->orWhereHas('contact', fn($q) => $q->where('name', 'like', "%{$request->search}%"));
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('date', '<=', $request->date_to);
        }

        $quotations = $query->paginate(15)->withQueryString();

        // Summary counts
        $counts = [
            'all'      => Quotation::count(),
            'draft'    => Quotation::where('status', 'draft')->count(),
            'sent'     => Quotation::where('status', 'sent')->count(),
            'accepted' => Quotation::where('status', 'accepted')->count(),
            'rejected' => Quotation::where('status', 'rejected')->count(),
        ];

        $statuses = Quotation::statuses();

        return view('tenant.quotations.index', compact(
            'quotations',
            'counts',
            'statuses'
        ));
    }

    // ── Create ────────────────────────────────────────────────────
    public function create(Request $request): View
    {
        $contacts = Contact::orderBy('name')->get(['id', 'name', 'company', 'phone', 'email', 'address', 'city', 'state', 'gst_number']);
        $leads    = Lead::orderBy('name')->get(['id', 'name', 'phone']);

        $contact = $request->filled('contact_id')
            ? Contact::where('id', $request->contact_id)
            ->where('tenant_id', auth()->user()->tenant_id)
            ->first()
            : null;

        $lead = $request->filled('lead_id')
            ? Lead::where('id', $request->lead_id)
            ->where('tenant_id', auth()->user()->tenant_id)
            ->first()
            : null;

        $number   = Quotation::generateNumber();
        $statuses = Quotation::statuses();
        $tenant   = auth()->user()->tenant;
        $products = Product::where('tenant_id', auth()->user()->tenant_id)->active()->orderBy('name')->get(['id','name','description','rate','tax_percent','hsn','unit']);

        return view('tenant.quotations.create', compact(
            'contacts',
            'leads',
            'contact',
            'lead',
            'number',
            'statuses',
            'tenant',
            'products'
        ));
    }

    // ── Store ─────────────────────────────────────────────────────
    public function store(QuotationRequest $request): RedirectResponse
    {
        $data = $request->validated();

        // Calculate totals
        $totals = Quotation::calculateTotals(
            $data['items'],
            $data['discount'] ?? 0,
            $data['tax_percent'] ?? 18
        );

        $quotation = Quotation::create(array_merge($data, $totals, [
            'tenant_id'  => auth()->user()->tenant_id,
            'number'     => Quotation::generateNumber(),
            'created_by' => auth()->id(),
            'status'     => $data['status'] ?? 'draft',
        ]));

        return redirect()
            ->route('tenant.quotations.show', $quotation->id)
            ->with('success', "Quotation {$quotation->number} created successfully.");
    }

    // ── Show ──────────────────────────────────────────────────────
    public function show(int|string $id): View
    {
        $quotation = $this->findQuotation($id);
        $quotation->load(['contact', 'lead', 'createdBy', 'invoice']);

        $tenant   = auth()->user()->tenant;
        $statuses = Quotation::statuses();

        return view('tenant.quotations.show', compact('quotation', 'tenant', 'statuses'));
    }

    // ── Edit ──────────────────────────────────────────────────────
    public function edit(int|string $id)
    {
        $quotation = $this->findQuotation($id);

        if ($quotation->status === 'accepted') {
            return redirect()
                ->route('tenant.quotations.show', $quotation->id)
                ->with('error', 'Accepted quotation cannot be edited.');
        }

        $contacts = Contact::orderBy('name')->get(['id', 'name', 'company', 'phone', 'email', 'address', 'city', 'state', 'gst_number']);
        $leads    = Lead::orderBy('name')->get(['id', 'name']);
        $statuses = Quotation::statuses();
        $tenant   = auth()->user()->tenant;
        $products = Product::where('tenant_id', auth()->user()->tenant_id)->active()->orderBy('name')->get(['id','name','description','rate','tax_percent','hsn','unit']);

        return view('tenant.quotations.edit', compact(
            'quotation',
            'contacts',
            'leads',
            'statuses',
            'tenant',
            'products'
        ));
    }

    // ── Update ────────────────────────────────────────────────────
    public function update(QuotationRequest $request, int|string $id): RedirectResponse
    {
        $quotation = $this->findQuotation($id);
        $data      = $request->validated();

        $totals = Quotation::calculateTotals(
            $data['items'],
            $data['discount'] ?? 0,
            $data['tax_percent'] ?? 18
        );

        $quotation->update(array_merge($data, $totals));

        return redirect()
            ->route('tenant.quotations.show', $quotation->id)
            ->with('success', 'Quotation updated successfully.');
    }

    // ── Destroy ───────────────────────────────────────────────────
    public function destroy(int|string $id): RedirectResponse
    {
        $quotation = $this->findQuotation($id);
        $number    = $quotation->number;
        $quotation->delete();

        return redirect()
            ->route('tenant.quotations.index')
            ->with('success', "Quotation {$number} deleted.");
    }

    // ── Update status ─────────────────────────────────────────────
    public function updateStatus(Request $request, int|string $id): RedirectResponse
    {
        $request->validate([
            'status' => ['required', 'in:draft,sent,accepted,rejected'],
        ]);

        $quotation = $this->findQuotation($id);
        $quotation->update(['status' => $request->status]);

        return back()->with('success', 'Quotation status updated.');
    }

    // ── Download PDF ──────────────────────────────────────────────
    public function pdf(int|string $id)
    {
        $quotation = $this->findQuotation($id);
        $quotation->load(['contact', 'createdBy']);
        $tenant = auth()->user()->tenant;

        $pdf = Pdf::loadView('tenant.quotations.pdf', compact('quotation', 'tenant'))
            ->setPaper('a4', 'portrait');

        return $pdf->download("Quotation-{$quotation->number}.pdf");
    }

    // ── Send via email ────────────────────────────────────────────
    public function send(int|string $id): RedirectResponse
    {
        $quotation = $this->findQuotation($id);
        $quotation->load('contact');

        if (!$quotation->contact?->email) {
            return back()->with('error', 'Contact has no email address.');
        }

        // TODO: Dispatch SendQuotationEmail job
        // SendQuotationEmail::dispatch($quotation);

        $quotation->update(['status' => 'sent']);

        return back()->with('success', "Quotation sent to {$quotation->contact->email}.");
    }

    // ── Convert to Invoice ────────────────────────────────────────
    public function convertToInvoice(int|string $id): RedirectResponse
    {
        $quotation = $this->findQuotation($id);

        if ($quotation->invoice) {
            return redirect()
                ->route('invoices.show', $quotation->invoice->id)
                ->with('info', 'Invoice already exists for this quotation.');
        }

        if ($quotation->status !== 'accepted') {
            return back()->with('error', 'Only accepted quotations can be converted to invoice.');
        }

        $invoice = Invoice::create([
            'tenant_id'      => $quotation->tenant_id,
            'contact_id'     => $quotation->contact_id,
            'quotation_id'   => $quotation->id,
            'number'         => Invoice::generateNumber(),
            'date'           => now()->toDateString(),
            'due_date'       => now()->addDays(30)->toDateString(),
            'items'          => $quotation->items,
            'subtotal'       => $quotation->subtotal,
            'discount'       => $quotation->discount,
            'tax_percent'    => $quotation->tax_percent,
            'tax_amount'     => $quotation->tax_amount,
            'total'          => $quotation->total,
            'notes'          => $quotation->notes,
            'terms'          => $quotation->terms,
            'status'         => 'draft',
            'created_by'     => auth()->id(),
        ]);

        return redirect()
            ->route('tenant.invoices.show', $invoice->id)
            ->with('success', "Invoice {$invoice->number} created from quotation {$quotation->number}.");
    }

    public function quotationData(string $tenant, int|string $quotation): JsonResponse
    {
        $quotation = $this->findQuotation($quotation);

        $quotation->load('contact');

        return response()->json([
            'success' => true,

            'data' => [

                /*
                |--------------------------------------------------------------------------
                | Basic
                |--------------------------------------------------------------------------
                */

                'quotation_id' => $quotation->id,

                'contact_id' => $quotation->contact_id,

                'customer' => [
                    'name'        => $quotation->contact?->name,
                    'company'     => $quotation->contact?->company,
                    'phone'       => $quotation->contact?->phone,
                    'email'       => $quotation->contact?->email,
                    'gst_number'  => $quotation->contact?->gst_number,
                    'address'     => $quotation->contact?->address,
                    'city'        => $quotation->contact?->city,
                    'state'       => $quotation->contact?->state,
                    'pincode'     => $quotation->contact?->pincode,
                ],

                /*
                |--------------------------------------------------------------------------
                | Invoice Financials
                |--------------------------------------------------------------------------
                */

                'items'        => $quotation->items ?? [],
                'subtotal'     => $quotation->subtotal ?? 0,
                'discount'     => $quotation->discount ?? 0,
                'tax_percent'  => $quotation->tax_percent ?? 18,
                'tax_amount'   => $quotation->tax_amount ?? 0,
                'total'        => $quotation->total ?? 0,

                /*
                |--------------------------------------------------------------------------
                | Terms
                |--------------------------------------------------------------------------
                */

                'notes' => $quotation->notes,
                'terms' => $quotation->terms,

                /*
                |--------------------------------------------------------------------------
                | Suggested Dates
                |--------------------------------------------------------------------------
                */

                'invoice_date' => now()->format('Y-m-d'),
                'due_date'     => now()->addDays(7)->format('Y-m-d'),
            ],
        ]);
    }
}
