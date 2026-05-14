<?php

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\InvoiceRequest;
use App\Models\Contact;
use App\Models\Invoice;
use App\Models\Quotation;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    private function tenantId(): int
    {
        return auth()->user()->tenant_id;
    }

    private function tenantSubdomain(): ?string
    {
        return auth()->user()?->tenant?->subdomain;
    }

    private function getInvoiceConfig(): array
    {
        return config('invoice_fields');
    }

    private function getStaffList()
    {
        return User::where('tenant_id', $this->tenantId())
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    private function getContacts()
    {
        return Contact::where('tenant_id', $this->tenantId())
            ->orderBy('name')
            ->get(['id', 'name', 'company']);
    }

    private function getQuotations()
    {
        return Quotation::where('tenant_id', $this->tenantId())
            ->latest()
            ->get(['id', 'number']);
    }

    /*
    |--------------------------------------------------------------------------
    | Find Invoice
    |--------------------------------------------------------------------------
    */

    private function findInvoice(int|string $id): Invoice
    {
        return Invoice::where('tenant_id', $this->tenantId())
            ->with([
                'contact',
                'quotation',
            ])
            ->findOrFail($id);
    }

    /*
    |--------------------------------------------------------------------------
    | Index
    |--------------------------------------------------------------------------
    */

    public function index(Request $request): View
    {
        $query = Invoice::query()
            ->with(['contact'])
            ->where('tenant_id', $this->tenantId());

        // ── Search ────────────────────────────────────────────────
        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('number', 'like', "%{$search}%")
                    ->orWhereHas('contact', function ($c) use ($search) {
                        $c->where('name', 'like', "%{$search}%")
                            ->orWhere('company', 'like', "%{$search}%");
                    });
            });
        }

        // ── Filters ───────────────────────────────────────────────
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('contact_id')) {
            $query->where('contact_id', $request->contact_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('date', '<=', $request->date_to);
        }

        // ── Sorting ───────────────────────────────────────────────
        $sort = $request->get('sort', 'created_at');
        $dir  = $request->get('dir', 'desc');

        $allowed = [
            'number',
            'date',
            'due_date',
            'total',
            'status',
            'created_at',
        ];

        if (in_array($sort, $allowed)) {
            $query->orderBy($sort, $dir === 'asc' ? 'asc' : 'desc');
        }

        $invoices = $query
            ->paginate(20)
            ->withQueryString();

        // ── Summary ───────────────────────────────────────────────
        $summary = [
            'all' => [
                'count' => Invoice::where('tenant_id', $this->tenantId())->count(),
                'amount' => Invoice::where('tenant_id', $this->tenantId())->sum('total'),
            ],

            'paid' => [
                'count' => Invoice::where('tenant_id', $this->tenantId())
                    ->where('status', 'paid')
                    ->count(),

                'amount' => Invoice::where('tenant_id', $this->tenantId())
                    ->where('status', 'paid')
                    ->sum('total'),
            ],

            'partial' => [
                'count' => Invoice::where('tenant_id', $this->tenantId())
                    ->where('status', 'partial')
                    ->count(),

                'amount' => Invoice::where('tenant_id', $this->tenantId())
                    ->where('status', 'partial')
                    ->sum('total'),
            ],

            'overdue' => [
                'count' => Invoice::where('tenant_id', $this->tenantId())
                    ->where('status', 'overdue')
                    ->count(),

                'amount' => Invoice::where('tenant_id', $this->tenantId())
                    ->where('status', 'overdue')
                    ->sum('total'),
            ],
        ];

        $statuses = $this->getInvoiceConfig()['statuses'] ?? [];

        return view('tenant.invoices.index', compact(
            'invoices',
            'summary',
            'statuses'
        ));
    }

    /*
    |--------------------------------------------------------------------------
    | Create
    |--------------------------------------------------------------------------
    */


    public function create(Request $request)
    {
        $contacts = Contact::where('tenant_id', $this->tenantId())
            ->orderBy('name')
            ->get();

        $quotations = Quotation::where('tenant_id', $this->tenantId())
            ->latest()
            ->get();

        $statuses = [
            'draft'   => 'Draft',
            'sent'    => 'Sent',
            'partial' => 'Partial Paid',
            'paid'    => 'Paid',
            'overdue' => 'Overdue',
        ];
        $lastInvoice = Invoice::where('tenant_id', $this->tenantId())
            ->latest('id')
            ->first();

        $nextNumber = 1;

        if ($lastInvoice && preg_match('/(\d+)$/', $lastInvoice->number, $match)) {
            $nextNumber = ((int) $match[1]) + 1;
        }
        $nextInvoiceNumber =
            'INV-' .
            date('Y') .
            '-' .
            str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
        return view('tenant.invoices.create', compact(
            'contacts',
            'quotations',
            'statuses',
            'nextInvoiceNumber'
        ));
    }


    /*
    |--------------------------------------------------------------------------
    | Store
    |--------------------------------------------------------------------------
    */

    public function store(InvoiceRequest $request): RedirectResponse
    {
        DB::beginTransaction();

        try {

            $data = $request->validated();

            // ── Totals calculation ────────────────────────────────
            $totals = $this->calculateTotals($data);

            $invoice = Invoice::create([
                'tenant_id'            => $this->tenantId(),
                'contact_id'           => $data['contact_id'] ?? null,
                'quotation_id'         => $data['quotation_id'] ?? null,
                'number'               => $this->generateInvoiceNumber(),
                'date'                 => $data['date'],
                'due_date'             => $data['due_date'] ?? null,
                'items'                => $data['items'],
                'subtotal'             => $totals['subtotal'],
                'tax_percent'          => $data['tax_percent'] ?? 0,
                'tax_amount'           => $totals['tax_amount'],
                'discount'             => $data['discount'] ?? 0,
                'total'                => $totals['total'],
                'paid_amount'          => $data['paid_amount'] ?? 0,
                'status'               => $data['status'] ?? 'draft',
                'razorpay_payment_id'  => $data['razorpay_payment_id'] ?? null,
                'paid_at'              => ($data['status'] ?? null) === 'paid'
                    ? now()
                    : null,
            ]);

            DB::commit();

            return redirect()
                ->route('tenant.invoices.show', $invoice->id)
                ->with('success', 'Invoice created successfully.');
        } catch (\Throwable $e) {

            DB::rollBack();

            return back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Show
    |--------------------------------------------------------------------------
    */

    public function show(int|string $id): View
    {
        $invoice = $this->findInvoice($id);

        return view('tenant.invoices.show', [
            'invoice' => $invoice,
            'config'  => $this->getInvoiceConfig(),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Edit
    |--------------------------------------------------------------------------
    */

    public function edit(int|string $id): View
    {
        $invoice = $this->findInvoice($id);

        $config = $this->getInvoiceConfig();

        return view('tenant.invoices.edit', [
            'invoice'    => $invoice,
            'config'     => $config,
            'contacts'   => $this->getContacts(),
            'quotations' => $this->getQuotations(),
            'staffList'  => $this->getStaffList(),
            'statuses'   => $config['statuses'] ?? [],
            'taxes'      => $config['taxes'] ?? [],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Update
    |--------------------------------------------------------------------------
    */

    public function update(
        InvoiceRequest $request,
        string $tenant,
        int|string $id
    ): RedirectResponse {

        DB::beginTransaction();

        try {

            $invoice = $this->findInvoice($id);

            $data = $request->validated();

            $totals = $this->calculateTotals($data);

            $invoice->update([
                'contact_id'           => $data['contact_id'] ?? null,
                'quotation_id'         => $data['quotation_id'] ?? null,
                'date'                 => $data['date'],
                'due_date'             => $data['due_date'] ?? null,
                'items'                => $data['items'],
                'subtotal'             => $totals['subtotal'],
                'tax_percent'          => $data['tax_percent'] ?? 0,
                'tax_amount'           => $totals['tax_amount'],
                'discount'             => $data['discount'] ?? 0,
                'total'                => $totals['total'],
                'paid_amount'          => $data['paid_amount'] ?? 0,
                'status'               => $data['status'],
                'paid_at'              => $data['status'] === 'paid'
                    ? now()
                    : null,
            ]);

            DB::commit();

            return redirect()
                ->route('tenant.invoices.show', $invoice->id)
                ->with('success', 'Invoice updated successfully.');
        } catch (\Throwable $e) {

            DB::rollBack();

            return back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Destroy
    |--------------------------------------------------------------------------
    */

    public function destroy(string $tenant, int|string $id): RedirectResponse
    {
        $invoice = $this->findInvoice($id);

        $invoice->delete();

        return redirect()
            ->route('tenant.invoices.index')
            ->with('success', 'Invoice deleted successfully.');
    }

    /*
    |--------------------------------------------------------------------------
    | Mark Paid
    |--------------------------------------------------------------------------
    */

    public function markPaid(
        Request $request,
        string $tenant,
        int|string $id
    ): RedirectResponse {

        $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
        ]);

        $invoice = $this->findInvoice($id);

        $newPaid = $invoice->paid_amount + $request->amount;

        $status = 'partial';

        if ($newPaid >= $invoice->total) {
            $status = 'paid';
        }

        $invoice->update([
            'paid_amount' => $newPaid,
            'status'      => $status,
            'paid_at'     => $status === 'paid' ? now() : null,
        ]);

        return back()->with('success', 'Payment updated.');
    }

    /*
    |--------------------------------------------------------------------------
    | Send Invoice
    |--------------------------------------------------------------------------
    */

    public function send(
        string $tenant,
        int|string $id
    ): RedirectResponse {

        $invoice = $this->findInvoice($id);

        if ($invoice->status === 'draft') {
            $invoice->update([
                'status' => 'sent',
            ]);
        }

        // Mail / WhatsApp logic later

        return back()->with('success', 'Invoice sent successfully.');
    }

    /*
    |--------------------------------------------------------------------------
    | PDF
    |--------------------------------------------------------------------------
    */

    public function pdf(string $tenant, int|string $id)
    {
        $invoice = $this->findInvoice($id);

        return view('tenant.invoices.pdf', [
            'invoice' => $invoice,
            'config'  => $this->getInvoiceConfig(),
        ]);

        /*
        later:
        return Pdf::loadView(...)->download(...)
        */
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    private function generateInvoiceNumber(): string
    {
        $prefix = config('invoice_fields.number_prefix', 'INV');

        $last = Invoice::where('tenant_id', $this->tenantId())
            ->latest('id')
            ->first();

        $next = $last ? ($last->id + 1) : 1;

        return $prefix . '-' . str_pad($next, 5, '0', STR_PAD_LEFT);
    }

    private function calculateTotals(array $data): array
    {
        $subtotal = 0;

        foreach ($data['items'] as $item) {

            $qty   = (float) ($item['qty'] ?? 1);
            $price = (float) ($item['price'] ?? 0);

            $subtotal += ($qty * $price);
        }

        $discount = (float) ($data['discount'] ?? 0);

        $taxPercent = (float) ($data['tax_percent'] ?? 0);

        $taxable = max(0, $subtotal - $discount);

        $taxAmount = ($taxable * $taxPercent) / 100;

        $total = $taxable + $taxAmount;

        return [
            'subtotal'  => round($subtotal, 2),
            'tax_amount' => round($taxAmount, 2),
            'total'     => round($total, 2),
        ];
    }
}
