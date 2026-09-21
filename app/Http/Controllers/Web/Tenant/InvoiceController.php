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
use App\Models\WhatsappLog;
use App\Models\WhatsappSetting;
use App\Services\EmailService;
use App\Services\InvoicePdfTemplateRenderer;
use App\Services\LoyaltyCampaignService;
use App\Services\LoyaltyService;
use App\Services\NotificationService;
use App\Services\StockService;
use App\Services\WebhookService;
use App\Services\WhatsappChatbotService;
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

    // GST split — the tenant's company is the supplier, the customer the
    // recipient.
    private function gstColumns($contactId, float $taxAmount): array
    {
        $contact = $contactId ? Contact::where('id', $contactId)->where('tenant_id', $this->tenantId())->first() : null;

        return \App\Services\GstService::documentColumns(
            $taxAmount,
            auth()->user()->tenant?->companyState(),
            \App\Services\GstService::partyState($contact),
        );
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
                'total_quantity' => $service->total_quantity,
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
            ->get(['id', 'name', 'company', 'phone', 'email', 'address', 'city', 'state', 'gst_number', 'loyalty_points', 'loyalty_tier']);

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
        $services = Service::where('tenant_id', $this->tenantId())->active()->orderBy('name')->get(['id','service_code','name','description','rate','tax_percent','hsn','unit','billing_cycle','duration_value','duration_unit','is_package']);

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
        $totals += $this->gstColumns($request->contact_id, (float) $totals['tax_amount']);

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

        // Loyalty redemption panel — only when the module is on, there is a
        // customer, and the bill isn't settled yet.
        $loyaltyQuote = null;
        if ($tenant->hasModuleEnabled('loyalty') && $invoice->contact && $invoice->status !== 'paid' && !$invoice->hasLoyaltyRedemption()) {
            $loyaltyQuote = app(LoyaltyService::class)->quoteRedemption($invoice);
        }

        return view('tenant.invoices.show', compact(
            'invoice', 'tenant', 'statuses', 'loyaltyQuote'
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

        if ($invoice->hasLoyaltyRedemption() || $invoice->hasCampaignCoupon() || $invoice->hasLoyaltyReward()) {
            return redirect()
                ->route('tenant.invoices.show', $invoice->id)
                ->with('error', 'Remove the loyalty redemption / coupon / reward before editing this invoice.');
        }

        $contacts = Contact::where('tenant_id', $this->tenantId())
            ->orderBy('name')
            ->get(['id', 'name', 'company', 'phone', 'email', 'address', 'city', 'state', 'gst_number']);

        $statuses = Invoice::statuses();
        $tenant   = auth()->user()->tenant;
        $products = Product::where('tenant_id', $this->tenantId())->active()->orderBy('name')->get(['id','product_code','name','description','rate','tax_percent','hsn','unit']);
        $services = Service::where('tenant_id', $this->tenantId())->active()->orderBy('name')->get(['id','service_code','name','description','rate','tax_percent','hsn','unit','billing_cycle','duration_value','duration_unit','is_package']);

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

        if ($invoice->hasLoyaltyRedemption() || $invoice->hasCampaignCoupon() || $invoice->hasLoyaltyReward()) {
            return back()->with('error', 'Remove the loyalty redemption / coupon / reward before editing this invoice.');
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
        $totals += $this->gstColumns($request->contact_id ?? $invoice->contact_id, (float) $totals['tax_amount']);

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

        // Return any loyalty points redeemed on this invoice to the customer.
        if ($invoice->hasLoyaltyRedemption()) {
            app(LoyaltyService::class)->reverseRedemption($invoice, auth()->id());
        }

        // Release a campaign coupon so the customer can use it elsewhere.
        if ($invoice->hasCampaignCoupon()) {
            app(LoyaltyCampaignService::class)->reverseCoupon($invoice);
        }

        // Return points spent on a catalog reward.
        if ($invoice->hasLoyaltyReward()) {
            app(LoyaltyService::class)->reverseReward($invoice, auth()->id());
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

        $invoice  = $this->findInvoice($id);
        $wasPaid  = $invoice->status === 'paid';
        $nowPaid  = $request->status === 'paid';
        $data     = ['status' => $request->status];

        if ($nowPaid) {
            // Loyalty points already tendered settle part of the bill, so cash
            // "paid" is the remainder.
            $data['paid_amount'] = max(0, (float) $invoice->total - (float) $invoice->loyalty_discount);
            $data['paid_at']     = now();
        }

        $invoice->update($data);

        if ($nowPaid) {
            $this->afterInvoicePaid($invoice);
        }

        // Status moved back off "paid" — claw back any loyalty points earned.
        if ($wasPaid && !$nowPaid) {
            app(LoyaltyService::class)->reverseForInvoice($invoice);
            app(LoyaltyService::class)->reverseStampForInvoice($invoice);
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

        $pdf = Pdf::loadView('tenant.invoices.pdf', [
                    'invoice'     => $invoice,
                    'tenant'      => $tenant,
                    'pdfSettings' => $pdfSettings,
                  ])
                  ->setPaper('a4', 'portrait');

        return $this->withPageNumbers($pdf, $pdfSettings->accent_color ?? '#3b82f6');
    }

    // ── Draw "Page N of M" into the footer of the default template ──
    // dompdf has no built-in CSS `counter(pages)` — reading its own
    // source confirms it: "page" is a real, tracked counter but "pages"
    // (total) is not special-cased anywhere, so `counter(pages)` in the
    // blade view's CSS silently renders as 0 always. The only way dompdf
    // actually knows the total page count is via its canvas-level
    // page_script()/page_text() API, which runs once rendering has
    // finished laying out every page (confirmed by reading
    // CPDF::processPageScript — it loops the already-built page list
    // immediately, it is not a deferred/lazy callback). So `render()`
    // must be called explicitly first; PDF::output()/download() then
    // see the wrapper's already-rendered flag and skip re-rendering,
    // serializing the pages this already drew into.
    //
    // Coordinates are physical PDF points (this API bypasses the CSS
    // margin/fixed-position layer entirely) chosen to right-align inside
    // the footer bar's right-hand cell in tenant.invoices.pdf — re-
    // verified by rendering and reading the exact text coordinates back
    // out of a produced PDF, the same way as that view's other
    // pixel-calculated fixed elements.
    private function withPageNumbers($pdf, string $accentColor)
    {
        $pdf->render();

        $canvas = $pdf->getDomPDF()->getCanvas();
        $rightEdge = 571.28; // page width 595.28pt − 32px (24pt) right padding
        $top       = 817.0;  // footer bar's physical top (810.39pt) + its 9px (6.75pt) padding-top
        $size      = 6.75;   // 9px
        [$r, $g, $b] = sscanf($accentColor, "#%02x%02x%02x") ?: [59, 130, 246];
        $color = [$r / 255, $g / 255, $b / 255];

        $canvas->page_script(function ($pageNumber, $pageCount, $canvas, $fontMetrics) use ($rightEdge, $top, $size, $color) {
            $font  = $fontMetrics->getFont('DejaVu Sans', 'bold');
            $text  = "Page {$pageNumber} of {$pageCount}";
            $width = $fontMetrics->getTextWidth($text, $font, $size);
            $canvas->text($rightEdge - $width, $top, $text, $font, $size, $color);
        });

        return $pdf;
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

    // ── Send via WhatsApp ─────────────────────────────────────────
    public function sendWhatsapp(int|string $id): RedirectResponse
    {
        $invoice = $this->findInvoice($id);
        $invoice->load('contact');

        $contact = $invoice->contact;
        if (!$contact?->phone) {
            return back()->with('error', 'Contact has no phone number.');
        }

        $settings = WhatsappSetting::forTenant($invoice->tenant_id);
        if (!$settings->exists || !$settings->is_connected) {
            return back()->with('error', 'WhatsApp is not connected. Please configure it in WhatsApp API Settings first.');
        }

        $service = WhatsappChatbotService::forTenant($invoice->tenant_id);
        $waId    = preg_replace('/[^0-9]/', '', $contact->phone);
        $tenant  = auth()->user()->tenant;

        $message = "Hi {$contact->name}, please find your invoice {$invoice->number} from {$tenant->name} for "
            . "₹" . number_format($invoice->total, 2) . ", due on {$invoice->due_date?->format('d M Y')}.";

        $pdfContent = $this->buildInvoicePdf($invoice)->output();

        $tmpPath = tempnam(sys_get_temp_dir(), 'inv') . '.pdf';
        file_put_contents($tmpPath, $pdfContent);

        $mediaId = $service->uploadMedia($tmpPath, 'application/pdf');
        $ok      = false;
        $error   = $service->lastError;

        if ($mediaId) {
            $ok    = $service->sendMediaMessage($waId, $mediaId, 'document', $message, "Invoice-{$invoice->number}.pdf");
            $error = $ok ? null : ($service->lastError ?? 'WhatsApp API rejected the media message.');
        }

        @unlink($tmpPath);

        WhatsappLog::create([
            'tenant_id'       => $invoice->tenant_id,
            'contact_id'      => $contact->id,
            'sent_by'         => auth()->id(),
            'to_phone'        => $contact->phone,
            'to_name'         => $contact->name,
            'message'         => $message,
            'status'          => $ok ? 'sent' : 'failed',
            'error_message'   => $error,
            'media_type'      => 'document',
            'media_id'        => $mediaId,
            'attachment_name' => "Invoice-{$invoice->number}.pdf",
            'sent_at'         => now(),
        ]);

        if (!$ok) {
            return back()->with('error', 'Failed to send WhatsApp message' . ($error ? ": {$error}" : '.'));
        }

        if ($invoice->status === 'draft') {
            $invoice->update(['status' => 'sent']);
        }

        return back()->with('success', "Invoice sent to {$contact->phone} via WhatsApp.");
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
        // Loyalty points redeemed on this invoice count toward settling it.
        $settled      = round($paidAmount + (float) $invoice->loyalty_discount, 2);
        $newStatus    = $settled >= (float) $invoice->total ? 'paid' : 'partial';
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

            // Award loyalty points + a stamp (idempotent, no-op if the module is off).
            app(LoyaltyService::class)->awardForInvoice($invoice);
            app(LoyaltyService::class)->awardStampForInvoice($invoice);
        }

        $count = count($rows);
        return back()->with('success', "{$count} payment(s) recorded. Status: " . ucfirst($newStatus));
    }

    // ── Loyalty — redeem points against this invoice ──────────────
    public function redeemLoyalty(Request $request, int|string $id): RedirectResponse
    {
        $invoice = $this->findInvoice($id);

        $request->validate([
            'points' => ['nullable', 'integer', 'min:1'],
            'use_max' => ['nullable', 'boolean'],
        ]);

        $requested = $request->boolean('use_max') ? null : $request->integer('points');
        if (!$request->boolean('use_max') && !$requested) {
            return back()->with('error', 'Enter how many points to redeem, or choose "use maximum".');
        }

        $result = app(LoyaltyService::class)->applyRedemption($invoice, $requested, auth()->id());

        if (!$result['ok']) {
            return back()->with('error', $result['message']);
        }

        // Points may have settled the bill in full.
        $invoice->refresh();
        if ($invoice->settledAmount() >= (float) $invoice->total && $invoice->status !== 'paid') {
            $invoice->update([
                'status'      => 'paid',
                'paid_amount' => max(0, (float) $invoice->total - (float) $invoice->loyalty_discount),
                'paid_at'     => now(),
            ]);
            $this->afterInvoicePaid($invoice);
        }

        return back()->with('success', $result['message']);
    }

    public function unredeemLoyalty(int|string $id): RedirectResponse
    {
        $invoice = $this->findInvoice($id);

        if ($invoice->status === 'paid') {
            return back()->with('error', 'Cannot change loyalty redemption on a settled invoice.');
        }

        if (!$invoice->hasLoyaltyRedemption()) {
            return back()->with('error', 'No loyalty points are redeemed on this invoice.');
        }

        app(LoyaltyService::class)->reverseRedemption($invoice, auth()->id());

        return back()->with('success', 'Loyalty redemption removed — points returned to the customer.');
    }

    // ── Loyalty — apply a campaign coupon code to this invoice ────
    public function applyCoupon(Request $request, int|string $id): RedirectResponse
    {
        $invoice = $this->findInvoice($id);

        $data = $request->validate(['code' => ['required', 'string', 'max:20']]);

        $result = app(LoyaltyCampaignService::class)->applyCoupon($invoice, $data['code'], auth()->id());

        if (!$result['ok']) {
            return back()->with('error', $result['message']);
        }

        $invoice->refresh();
        if ($invoice->settledAmount() >= (float) $invoice->total && $invoice->status !== 'paid') {
            $invoice->update([
                'status'      => 'paid',
                'paid_amount' => max(0, (float) $invoice->total - (float) $invoice->loyalty_discount - (float) $invoice->campaign_discount),
                'paid_at'     => now(),
            ]);
            $this->afterInvoicePaid($invoice);
        }

        return back()->with('success', trim($result['message'] . ' ' . ($result['note'] ?? '')));
    }

    public function removeCoupon(int|string $id): RedirectResponse
    {
        $invoice = $this->findInvoice($id);

        if ($invoice->status === 'paid') {
            return back()->with('error', 'Cannot change the coupon on a settled invoice.');
        }

        if (!$invoice->hasCampaignCoupon()) {
            return back()->with('error', 'No campaign coupon is applied to this invoice.');
        }

        app(LoyaltyCampaignService::class)->reverseCoupon($invoice);

        return back()->with('success', 'Campaign coupon removed.');
    }

    // ── Loyalty — redeem a standing catalog reward (free item) ────
    public function redeemReward(Request $request, int|string $id): RedirectResponse
    {
        $invoice = $this->findInvoice($id);

        $data = $request->validate(['reward' => ['required', 'string', 'max:120']]);

        $result = app(LoyaltyService::class)->redeemReward($invoice, $data['reward'], auth()->id());

        return back()->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

    public function removeReward(int|string $id): RedirectResponse
    {
        $invoice = $this->findInvoice($id);

        if ($invoice->status === 'paid') {
            return back()->with('error', 'Cannot change the reward on a settled invoice.');
        }

        if (!$invoice->hasLoyaltyReward()) {
            return back()->with('error', 'No reward is redeemed on this invoice.');
        }

        app(LoyaltyService::class)->reverseReward($invoice, auth()->id());

        return back()->with('success', 'Reward removed — points returned to the customer.');
    }

    // Shared post-"paid" side effects (webhook + notification + loyalty earn).
    private function afterInvoicePaid(Invoice $invoice): void
    {
        WebhookService::fire('invoice.paid', $invoice->tenant_id, [
            'id'           => $invoice->id,
            'number'       => $invoice->number,
            'total'        => $invoice->total,
            'paid_at'      => now()->toIso8601String(),
            'contact_name' => $invoice->contact?->name,
        ]);

        app(LoyaltyService::class)->awardForInvoice($invoice);
        app(LoyaltyService::class)->awardStampForInvoice($invoice);
    }
}