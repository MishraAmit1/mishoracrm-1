<?php

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Invoice;
use App\Models\InvoicePdfSetting;
use App\Models\Product;
use App\Models\Quotation;
use App\Services\InvoicePdfTemplateRenderer;
use App\Services\WebhookService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    private function tenantId(): int
    {
        return auth()->user()->tenant_id;
    }

    private function findInvoice(int|string $id): Invoice
    {
        return Invoice::where('id', $id)
            ->where('tenant_id', $this->tenantId())
            ->firstOrFail();
    }

    // ── Index ─────────────────────────────────────────────────────
    public function index(Request $request): View
    {
        $query = Invoice::with(['contact', 'quotation', 'createdBy'])
            ->where('tenant_id', $this->tenantId())
            ->latest();

        if ($request->filled('status'))    $query->where('status', $request->status);
        if ($request->filled('search')) {
            $query->where(fn($q) =>
                $q->where('number', 'like', "%{$request->search}%")
                  ->orWhereHas('contact', fn($q) => $q->where('name', 'like', "%{$request->search}%"))
            );
        }
        if ($request->filled('date_from')) $query->whereDate('date', '>=', $request->date_from);
        if ($request->filled('date_to'))   $query->whereDate('date', '<=', $request->date_to);

        $invoices = $query->paginate(15)->withQueryString();

        $counts = [
            'all'     => Invoice::where('tenant_id', $this->tenantId())->count(),
            'draft'   => Invoice::where('tenant_id', $this->tenantId())->where('status', 'draft')->count(),
            'sent'    => Invoice::where('tenant_id', $this->tenantId())->where('status', 'sent')->count(),
            'paid'    => Invoice::where('tenant_id', $this->tenantId())->where('status', 'paid')->count(),
            'partial' => Invoice::where('tenant_id', $this->tenantId())->where('status', 'partial')->count(),
            'overdue' => Invoice::where('tenant_id', $this->tenantId())->overdue()->count(),
        ];

        $revenue = [
            'total_paid'    => Invoice::where('tenant_id', $this->tenantId())->where('status', 'paid')->sum('total'),
            'total_pending' => Invoice::where('tenant_id', $this->tenantId())->whereIn('status', ['sent', 'partial'])->sum('total'),
            'total_overdue' => Invoice::where('tenant_id', $this->tenantId())->overdue()->sum('total'),
        ];

        $statuses = Invoice::statuses();

        return view('tenant.invoices.index', compact(
            'invoices', 'counts', 'revenue', 'statuses'
        ));
    }

    // ── Create ────────────────────────────────────────────────────
    public function create(Request $request): View
    {
        $contacts = Contact::where('tenant_id', $this->tenantId())
            ->orderBy('name')
            ->get(['id', 'name', 'company', 'phone', 'email', 'address', 'city', 'state', 'gst_number']);

        $contact = $request->filled('contact_id')
            ? Contact::where('id', $request->contact_id)->where('tenant_id', $this->tenantId())->first()
            : null;

        // Pre-fill from quotation if passed
        $quotation = $request->filled('quotation_id')
            ? Quotation::where('id', $request->quotation_id)->where('tenant_id', $this->tenantId())->first()
            : null;

        $number   = Invoice::generateNumber();
        $statuses = Invoice::statuses();
        $tenant   = auth()->user()->tenant;
        $products = Product::where('tenant_id', $this->tenantId())->active()->orderBy('name')->get(['id','product_code','name','description','rate','tax_percent','hsn','unit']);

        return view('tenant.invoices.create', compact(
            'contacts', 'contact', 'quotation',
            'number', 'statuses', 'tenant', 'products'
        ));
    }

    // ── Store ─────────────────────────────────────────────────────
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'contact_id'  => ['required', 'exists:contacts,id'],
            'date'        => ['required', 'date'],
            'due_date'    => ['required', 'date', 'after_or_equal:date'],
            'items'       => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string'],
            'items.*.quantity'    => ['required', 'numeric', 'min:0.01'],
            'items.*.rate'        => ['required', 'numeric', 'min:0'],
            'discount'    => ['nullable', 'numeric', 'min:0'],
            'tax_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'notes'       => ['nullable', 'string'],
            'terms'       => ['nullable', 'string'],
        ]);

        $items  = $request->items;
        $totals = Invoice::calculateTotals(
            $items,
            $request->discount ?? 0,
            $request->tax_percent ?? 18
        );

        $invoice = Invoice::create(array_merge($totals, [
            'tenant_id'    => $this->tenantId(),
            'contact_id'   => $request->contact_id,
            'quotation_id' => $request->quotation_id,
            'number'       => Invoice::generateNumber(),
            'date'         => $request->date,
            'due_date'     => $request->due_date,
            'items'        => $items,
            'notes'        => $request->notes,
            'terms'        => $request->terms,
            'status'       => 'draft',
            'paid_amount'  => 0,
            'created_by'   => auth()->id(),
        ]));

        WebhookService::fire('invoice.created', $invoice->tenant_id, [
            'id'           => $invoice->id,
            'number'       => $invoice->number,
            'total'        => $invoice->total,
            'due_date'     => $invoice->due_date,
            'contact_name' => $invoice->contact?->name,
            'contact_phone'=> $invoice->contact?->phone,
        ]);

        return redirect()
            ->route('tenant.invoices.show', $invoice->id)
            ->with('success', "Invoice {$invoice->number} created.");
    }

    // ── Show ──────────────────────────────────────────────────────
    public function show(int|string $id): View
    {
        $invoice = $this->findInvoice($id);
        $invoice->load(['contact', 'quotation', 'createdBy']);

        $tenant   = auth()->user()->tenant;
        $statuses = Invoice::statuses();

        return view('tenant.invoices.show', compact(
            'invoice', 'tenant', 'statuses'
        ));
    }

    // ── Edit ──────────────────────────────────────────────────────
    public function edit(int|string $id): View
    {
        $invoice = $this->findInvoice($id);

        if ($invoice->status === 'paid') {
            return redirect()
                ->route('tenant.invoices.show', $invoice->id)
                ->with('error', 'Paid invoice cannot be edited.');
        }

        $contacts = Contact::where('tenant_id', $this->tenantId())
            ->orderBy('name')
            ->get(['id', 'name', 'company', 'phone', 'email', 'address', 'city', 'state', 'gst_number']);

        $statuses = Invoice::statuses();
        $tenant   = auth()->user()->tenant;
        $products = Product::where('tenant_id', $this->tenantId())->active()->orderBy('name')->get(['id','product_code','name','description','rate','tax_percent','hsn','unit']);

        return view('tenant.invoices.edit', compact(
            'invoice', 'contacts', 'statuses', 'tenant', 'products'
        ));
    }

    // ── Update ────────────────────────────────────────────────────
    public function update(Request $request, int|string $id): RedirectResponse
    {
        $invoice = $this->findInvoice($id);

        if ($invoice->status === 'paid') {
            return back()->with('error', 'Paid invoice cannot be edited.');
        }

        $request->validate([
            'contact_id'  => ['required', 'exists:contacts,id'],
            'date'        => ['required', 'date'],
            'due_date'    => ['required', 'date', 'after_or_equal:date'],
            'items'       => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string'],
            'items.*.quantity'    => ['required', 'numeric', 'min:0.01'],
            'items.*.rate'        => ['required', 'numeric', 'min:0'],
            'discount'    => ['nullable', 'numeric', 'min:0'],
            'tax_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'notes'       => ['nullable', 'string'],
            'terms'       => ['nullable', 'string'],
        ]);

        $items  = $request->items;
        $totals = Invoice::calculateTotals(
            $items,
            $request->discount ?? 0,
            $request->tax_percent ?? $invoice->tax_percent
        );

        $invoice->update(array_merge($totals, [
            'contact_id' => $request->contact_id,
            'date'       => $request->date,
            'due_date'   => $request->due_date,
            'items'      => $items,
            'notes'      => $request->notes,
            'terms'      => $request->terms,
        ]));

        return redirect()
            ->route('tenant.invoices.show', $invoice->id)
            ->with('success', 'Invoice updated.');
    }

    // ── Destroy ───────────────────────────────────────────────────
    public function destroy(int|string $id): RedirectResponse
    {
        $invoice = $this->findInvoice($id);

        if ($invoice->status === 'paid') {
            return back()->with('error', 'Paid invoice cannot be deleted.');
        }

        $number = $invoice->number;
        $invoice->delete();

        return redirect()
            ->route('tenant.invoices.index')
            ->with('success', "Invoice {$number} deleted.");
    }

    // ── Update status ─────────────────────────────────────────────
    public function updateStatus(Request $request, int|string $id): RedirectResponse
    {
        $request->validate([
            'status' => ['required', 'in:draft,sent,paid,partial,overdue'],
        ]);

        $invoice = $this->findInvoice($id);
        $data    = ['status' => $request->status];

        if ($request->status === 'paid') {
            $data['paid_amount'] = $invoice->total;
            $data['paid_at']     = now();
        }

        $invoice->update($data);

        if ($request->status === 'paid') {
            WebhookService::fire('invoice.paid', $invoice->tenant_id, [
                'id'           => $invoice->id,
                'number'       => $invoice->number,
                'total'        => $invoice->total,
                'paid_at'      => now()->toIso8601String(),
                'contact_name' => $invoice->contact?->name,
            ]);
        }

        return back()->with('success', 'Invoice status updated.');
    }

    // ── Record payment ────────────────────────────────────────────
    public function recordPayment(Request $request, int|string $id): RedirectResponse
    {
        $request->validate([
            'paid_amount' => ['required', 'numeric', 'min:0.01'],
            'paid_at'     => ['required', 'date'],
            'payment_note'=> ['nullable', 'string', 'max:255'],
        ]);

        $invoice    = $this->findInvoice($id);
        $newPaid    = round($invoice->paid_amount + $request->paid_amount, 2);
        $newStatus  = $newPaid >= $invoice->total ? 'paid' : 'partial';

        $invoice->update([
            'paid_amount' => min($newPaid, $invoice->total),
            'paid_at'     => $newStatus === 'paid' ? $request->paid_at : $invoice->paid_at,
            'status'      => $newStatus,
        ]);

        if ($newStatus === 'paid') {
            WebhookService::fire('invoice.paid', $invoice->tenant_id, [
                'id'           => $invoice->id,
                'number'       => $invoice->number,
                'total'        => $invoice->total,
                'paid_at'      => $request->paid_at,
                'contact_name' => $invoice->contact?->name,
            ]);
        }

        return back()->with('success', 'Payment recorded. Status: ' . ucfirst($newStatus));
    }

    // ── Download PDF ──────────────────────────────────────────────
    public function pdf(int|string $id)
    {
        $invoice = $this->findInvoice($id);
        $invoice->load(['contact', 'createdBy', 'quotation']);
        $tenant = auth()->user()->tenant;

        $pdfSettings = InvoicePdfSetting::where('tenant_id', $this->tenantId())->first();

        if ($pdfSettings && $pdfSettings->use_custom_template && $pdfSettings->custom_html) {
            $renderedHtml = InvoicePdfTemplateRenderer::render($pdfSettings->custom_html, $invoice, $tenant);

            $pdf = Pdf::loadView('tenant.invoices.custom-pdf', [
                'invoice'      => $invoice,
                'renderedHtml' => $renderedHtml,
                'fontFamily'   => $pdfSettings->font_family,
                'primaryColor' => $pdfSettings->primary_color,
                'accentColor'  => $pdfSettings->accent_color,
            ])->setPaper('a4', 'portrait');
        } else {
            $pdf = Pdf::loadView('tenant.invoices.pdf', compact('invoice', 'tenant'))
                      ->setPaper('a4', 'portrait');
        }

        return $pdf->download("Invoice-{$invoice->number}.pdf");
    }

    // ── Send via email ────────────────────────────────────────────
    public function send(int|string $id): RedirectResponse
    {
        $invoice = $this->findInvoice($id);
        $invoice->load('contact');

        if (!$invoice->contact?->email) {
            return back()->with('error', 'Contact has no email address.');
        }

        // TODO: Dispatch SendInvoiceEmail job
        $invoice->update(['status' => 'sent']);

        return back()->with('success', "Invoice sent to {$invoice->contact->email}.");
    }
}