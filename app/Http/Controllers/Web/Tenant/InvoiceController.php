<?php

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Invoice;
use App\Models\InvoicePdfSetting;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\Service;
use App\Models\ServiceSubscription;
use App\Services\EmailService;
use App\Services\InvoicePdfTemplateRenderer;
use App\Services\NotificationService;
use App\Services\StockService;
use App\Services\WebhookService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
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

    // Auto-creates a ServiceSubscription for any line item the staff
    // explicitly checked "Track as subscription" on — idempotent per
    // (invoice, service) pair so re-saving the same invoice never
    // creates duplicates. Unchecking the box on a later edit does NOT
    // remove a subscription already created; cancel it manually instead.
    private function syncSubscriptionsFromItems(Invoice $invoice, array $items): void
    {
        foreach ($items as $item) {
            if (empty($item['track_subscription']) || empty($item['service_id'])) {
                continue;
            }

            $service = Service::where('tenant_id', $this->tenantId())->find($item['service_id']);
            if (!$service) {
                continue;
            }

            $alreadyTracked = ServiceSubscription::where('invoice_id', $invoice->id)
                ->where('service_id', $service->id)
                ->exists();
            if ($alreadyTracked) {
                continue;
            }

            $expiresAt = ServiceSubscription::computeExpiry(
                $invoice->date,
                $service->duration_value,
                $service->duration_unit,
                $service->billing_cycle
            );

            ServiceSubscription::create([
                'tenant_id'      => $this->tenantId(),
                'contact_id'     => $invoice->contact_id,
                'service_id'     => $service->id,
                'invoice_id'     => $invoice->id,
                'starts_at'      => $invoice->date,
                'expires_at'     => $expiresAt,
                'duration_value' => $service->duration_value,
                'duration_unit'  => $service->duration_unit,
                'status'         => 'active',
            ]);
        }
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
        $services = Service::where('tenant_id', $this->tenantId())->active()->orderBy('name')->get(['id','service_code','name','description','rate','tax_percent','hsn','unit','billing_cycle','duration_value','duration_unit']);

        return view('tenant.invoices.create', compact(
            'contacts', 'contact', 'quotation',
            'number', 'statuses', 'tenant', 'products', 'services'
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
            'items.*.product_id'  => ['nullable', 'integer', 'exists:products,id'],
            'items.*.service_id'  => ['nullable', 'integer', 'exists:services,id'],
            'items.*.description' => ['required', 'string'],
            'items.*.quantity'    => ['required', 'numeric', 'min:0.01'],
            'items.*.rate'        => ['required', 'numeric', 'min:0'],
            'items.*.track_subscription' => ['nullable', 'boolean'],
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

        // Sale reduces finished-good stock for any item linked to a product.
        StockService::applyInvoiceItems($items, -1);

        $this->syncSubscriptionsFromItems($invoice, $items);

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
        $invoice->load(['contact.employees', 'quotation', 'createdBy', 'payments.recordedBy']);

        $tenant   = auth()->user()->tenant;
        $statuses = Invoice::statuses();

        return view('tenant.invoices.show', compact(
            'invoice', 'tenant', 'statuses'
        ));
    }

    // ── Edit ──────────────────────────────────────────────────────
    public function edit(int|string $id): View|RedirectResponse
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
        $services = Service::where('tenant_id', $this->tenantId())->active()->orderBy('name')->get(['id','service_code','name','description','rate','tax_percent','hsn','unit','billing_cycle','duration_value','duration_unit']);

        return view('tenant.invoices.edit', compact(
            'invoice', 'contacts', 'statuses', 'tenant', 'products', 'services'
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
            'items.*.product_id'  => ['nullable', 'integer', 'exists:products,id'],
            'items.*.service_id'  => ['nullable', 'integer', 'exists:services,id'],
            'items.*.description' => ['required', 'string'],
            'items.*.quantity'    => ['required', 'numeric', 'min:0.01'],
            'items.*.rate'        => ['required', 'numeric', 'min:0'],
            'items.*.track_subscription' => ['nullable', 'boolean'],
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

        // Undo the old item quantities' stock effect, then apply the new
        // ones — a correct net delta even if items/quantities changed.
        StockService::applyInvoiceItems($invoice->items ?? [], +1);

        $invoice->update(array_merge($totals, [
            'contact_id' => $request->contact_id,
            'date'       => $request->date,
            'due_date'   => $request->due_date,
            'items'      => $items,
            'notes'      => $request->notes,
            'terms'      => $request->terms,
        ]));

        StockService::applyInvoiceItems($items, -1);

        $this->syncSubscriptionsFromItems($invoice, $items);

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

        // Restore stock — only reachable for draft/sent/partial invoices
        // since paid invoices already block deletion above.
        StockService::applyInvoiceItems($invoice->items ?? [], +1);

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

    // ── Build the invoice PDF (default or tenant's custom template) ─
    private function buildInvoicePdf(Invoice $invoice)
    {
        $invoice->loadMissing(['contact', 'createdBy', 'quotation']);
        $tenant = auth()->user()->tenant;

        $pdfSettings = InvoicePdfSetting::where('tenant_id', $this->tenantId())->first();

        if ($pdfSettings && $pdfSettings->use_custom_template && $pdfSettings->custom_html) {
            $renderedHtml = InvoicePdfTemplateRenderer::render($pdfSettings->custom_html, $invoice, $tenant);

            return Pdf::loadView('tenant.invoices.custom-pdf', [
                'invoice'      => $invoice,
                'renderedHtml' => $renderedHtml,
                'fontFamily'   => $pdfSettings->font_family,
                'primaryColor' => $pdfSettings->primary_color,
                'accentColor'  => $pdfSettings->accent_color,
            ])->setPaper('a4', 'portrait');
        }

        return Pdf::loadView('tenant.invoices.pdf', [
                    'invoice'     => $invoice,
                    'tenant'      => $tenant,
                    'pdfSettings' => $pdfSettings,
                  ])
                  ->setPaper('a4', 'portrait');
    }

    // ── Download PDF ──────────────────────────────────────────────
    public function pdf(int|string $id)
    {
        $invoice = $this->findInvoice($id);

        return $this->buildInvoicePdf($invoice)->download("Invoice-{$invoice->number}.pdf");
    }

    // ── Send via email ────────────────────────────────────────────
    // To = contact's primary contact (primary employee's email, or the
    // contact's own email if no primary employee is set). Cc = every
    // other known email (other employees, contact's own email if unused).
    public function send(int|string $id): RedirectResponse
    {
        $invoice = $this->findInvoice($id);
        $invoice->load(['contact.employees']);

        $contact = $invoice->contact;
        $email   = $contact?->primaryEmail();

        if (!$email) {
            return back()->with('error', 'Contact has no email address.');
        }

        $name = $contact->employees->firstWhere('is_primary', true)?->name ?: $contact->name;
        $cc   = $contact->ccEmails();

        $tenant  = auth()->user()->tenant;
        $subject = "Invoice {$invoice->number} from {$tenant->name}";
        $html    = "<p>Dear {$name},</p>"
            . "<p>Please find attached invoice <strong>{$invoice->number}</strong> for "
            . "<strong>₹" . number_format($invoice->total, 2) . "</strong>, due on "
            . "{$invoice->due_date?->format('d M Y')}.</p>"
            . "<p>Thank you for your business.</p><p>{$tenant->name}</p>";

        $pdfContent = $this->buildInvoicePdf($invoice)->output();
        $attachments = [[
            'content' => $pdfContent,
            'name'    => "Invoice-{$invoice->number}.pdf",
            'mime'    => 'application/pdf',
        ]];

        $sent = EmailService::send($invoice->tenant_id, $email, $name, $subject, $html, $attachments, $cc);

        if (!$sent) {
            try {
                Mail::send([], [], function ($mail) use ($email, $name, $cc, $subject, $html, $pdfContent, $invoice) {
                    $mail->to($email, $name)
                         ->subject($subject)
                         ->html($html)
                         ->attachData($pdfContent, "Invoice-{$invoice->number}.pdf", ['mime' => 'application/pdf']);

                    foreach ($cc as $ccRecipient) {
                        $mail->cc($ccRecipient['email'], $ccRecipient['name'] ?? null);
                    }
                });
            } catch (\Exception $e) {
                return back()->with('error', "Could not send email: {$e->getMessage()}");
            }
        }

        if ($invoice->status === 'draft') {
            $invoice->update(['status' => 'sent']);
        }

        $ccNote = count($cc) ? ' (cc: ' . count($cc) . ')' : '';

        return back()->with('success', "Invoice sent to {$email}{$ccNote}.");
    }

    // ── Record payment(s) — one or more line items in a single submit ─
    public function recordPayment(Request $request, int|string $id): RedirectResponse
    {
        $invoice = $this->findInvoice($id);

        $request->validate([
            'payments'               => ['required', 'array', 'min:1'],
            'payments.*.amount'      => ['required', 'numeric', 'min:0.01'],
            'payments.*.method'      => ['required', 'in:' . implode(',', array_keys(Invoice::paymentMethods()))],
            'payments.*.paid_at'     => ['required', 'date'],
            'payments.*.note'        => ['nullable', 'string', 'max:255'],
        ]);

        $rows      = $request->payments;
        $newTotal  = collect($rows)->sum('amount');

        if (round($newTotal, 2) > round($invoice->due_amount, 2) + 0.01) {
            return back()->with('error', 'Total payment amount exceeds the due amount.')->withInput();
        }

        foreach ($rows as $row) {
            $invoice->payments()->create([
                'amount'      => $row['amount'],
                'method'      => $row['method'],
                'paid_at'     => $row['paid_at'],
                'note'        => $row['note'] ?? null,
                'recorded_by' => auth()->id(),
            ]);
        }

        $paidAmount   = $invoice->payments()->sum('amount');
        $newStatus    = $paidAmount >= $invoice->total ? 'paid' : 'partial';
        $latestPaidAt = collect($rows)->max('paid_at');

        $invoice->update([
            'paid_amount' => $paidAmount,
            'status'      => $newStatus,
            'paid_at'     => $newStatus === 'paid' ? $latestPaidAt : $invoice->paid_at,
        ]);

        if ($newStatus === 'paid') {
            WebhookService::fire('invoice.paid', $invoice->tenant_id, [
                'id'           => $invoice->id,
                'number'       => $invoice->number,
                'total'        => $invoice->total,
                'paid_at'      => $latestPaidAt,
                'contact_name' => $invoice->contact?->name,
            ]);

            if ($invoice->createdBy) {
                app(NotificationService::class)->send(
                    'invoice.paid',
                    $invoice->createdBy,
                    ['number' => $invoice->number, 'amount' => number_format($invoice->total, 2)],
                    null,
                    route('tenant.invoices.show', $invoice->id),
                    $invoice
                );
            }
        }

        $count = count($rows);
        return back()->with('success', "{$count} payment(s) recorded. Status: " . ucfirst($newStatus));
    }
}