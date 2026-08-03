<?php

namespace App\Services;

use App\Helpers\NumberToWords;
use App\Models\Invoice;
use App\Models\Tenant;

class InvoicePdfTemplateRenderer
{
    private const BLOCK_KEYS = ['items_table', 'totals_block', 'bank_details', 'signature_block', 'amount_in_words'];

    public static function render(string $template, Invoice $invoice, Tenant $tenant): string
    {
        // Quill wraps loose lines in <p>; unwrap block tokens so a table/div
        // isn't nested inside a <p>, which dompdf renders incorrectly.
        $template = preg_replace(
            '/<p[^>]*>\s*(\{\{\s*(' . implode('|', self::BLOCK_KEYS) . ')\s*\}\})\s*<\/p>/i',
            '$1',
            $template
        );

        $tokens = array_merge(
            self::simpleTokens($invoice, $tenant),
            self::blockTokens($invoice, $tenant)
        );

        return preg_replace_callback('/\{\{\s*([a-zA-Z_]+)\s*\}\}/', function ($m) use ($tokens) {
            return $tokens[$m[1]] ?? $m[0];
        }, $template);
    }

    private static function simpleTokens(Invoice $invoice, Tenant $tenant): array
    {
        $settings = $tenant->settings ?? [];

        return [
            'company_name'    => e($tenant->name),
            'company_email'   => e($tenant->email),
            'company_phone'   => e($tenant->phone),
            'company_address' => e($settings['address'] ?? ''),
            'company_gstin'   => e($settings['gstin'] ?? ''),
            'company_logo'    => $tenant->logo
                ? '<img src="' . public_path('storage/' . $tenant->logo) . '" style="max-height:60px;max-width:180px;">'
                : '',

            'invoice_number' => e($invoice->number),
            'invoice_date'   => $invoice->date->format('d M Y'),
            'due_date'       => $invoice->due_date->format('d M Y'),
            'invoice_status' => strtoupper($invoice->status),

            'customer_name'    => e($invoice->contact->name ?? ''),
            'customer_company' => e($invoice->contact->company ?? ''),
            'customer_email'   => e($invoice->contact->email ?? ''),
            'customer_phone'   => e($invoice->contact->phone ?? ''),
            'customer_address' => e($invoice->contact->address ?? ''),
            'customer_gstin'   => e($invoice->contact->gst_number ?? ''),
        ];
    }

    private static function blockTokens(Invoice $invoice, Tenant $tenant): array
    {
        return [
            'items_table'     => view('tenant.invoices.partials.items-table', compact('invoice'))->render(),
            'totals_block'    => view('tenant.invoices.partials.totals-block', compact('invoice'))->render(),
            'bank_details'    => view('tenant.invoices.partials.bank-details', compact('tenant'))->render(),
            'signature_block' => view('tenant.invoices.partials.signature-block', compact('invoice', 'tenant'))->render(),
            'amount_in_words' => '<div style="font-size:12px;font-weight:600;font-style:italic;">'
                . e(NumberToWords::convert($invoice->total)) . ' Only</div>',
        ];
    }
}
