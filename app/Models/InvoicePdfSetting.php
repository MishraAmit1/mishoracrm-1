<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoicePdfSetting extends Model
{
    protected $fillable = [
        'tenant_id',
        'primary_color',
        'accent_color',
        'font_family',
        'logo_position',
        'footer_note',
        'show_bank_details',
        'show_tax_summary',
        'use_custom_template',
        'custom_html',
    ];

    protected $casts = [
        'show_bank_details'   => 'boolean',
        'show_tax_summary'    => 'boolean',
        'use_custom_template' => 'boolean',
    ];

    public static function forTenant(int $tenantId): self
    {
        return static::firstOrNew(['tenant_id' => $tenantId], [
            'primary_color' => '#1e3a5f',
            'accent_color'  => '#3b82f6',
            'font_family'   => 'DejaVu Sans',
            'logo_position' => 'left',
            'show_bank_details' => true,
            'show_tax_summary'  => true,
        ]);
    }

    public static function fonts(): array
    {
        return ['DejaVu Sans', 'Helvetica', 'Times New Roman', 'Courier New'];
    }

    public static function variableGroups(): array
    {
        return [
            'Company' => [
                'company_name'    => 'Company Name',
                'company_email'   => 'Company Email',
                'company_phone'   => 'Company Phone',
                'company_address' => 'Company Address',
                'company_gstin'   => 'Company GSTIN',
                'company_logo'    => 'Company Logo (image)',
            ],
            'Invoice' => [
                'invoice_number' => 'Invoice Number',
                'invoice_date'   => 'Invoice Date',
                'due_date'       => 'Due Date',
                'invoice_status' => 'Status',
            ],
            'Customer' => [
                'customer_name'    => 'Customer Name',
                'customer_company' => 'Customer Company',
                'customer_email'   => 'Customer Email',
                'customer_phone'   => 'Customer Phone',
                'customer_address' => 'Customer Address',
                'customer_gstin'   => 'Customer GSTIN',
            ],
            'Blocks (auto-generated)' => [
                'items_table'     => 'Items Table',
                'totals_block'    => 'Totals (Subtotal/GST/Grand Total)',
                'amount_in_words' => 'Amount in Words',
                'bank_details'    => 'Bank Details Box',
                'signature_block' => 'Signature Box',
            ],
        ];
    }
}
