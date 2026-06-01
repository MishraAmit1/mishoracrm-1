<?php

namespace Database\Seeders;

use App\Models\CustomField;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class CustomFieldSeeder extends Seeder
{
    public function run(): void
    {
        $tenants = Tenant::all();

        if ($tenants->isEmpty()) {
            $this->command->warn('No tenants found. Run TenantSeeder first.');
            return;
        }

        foreach ($tenants as $tenant) {
            $this->seedLeadFields($tenant->id);
            $this->seedContactFields($tenant->id);
            $this->seedDealFields($tenant->id);
            $this->seedQuotationFields($tenant->id);
            $this->seedTaskFields($tenant->id);
        }

        $this->command->info('Custom fields seeded successfully.');
    }

    // ── Lead Fields ───────────────────────────────────────────────
    private function seedLeadFields(int $tenantId): void
    {
        $fields = [
            [
                'label'          => 'Budget Range',
                'field_key'      => 'budget_range',
                'field_type'     => 'dropdown',
                'options'        => ['Under ₹50K', '₹50K - ₹1L', '₹1L - ₹5L', '₹5L - ₹10L', 'Above ₹10L'],
                'placeholder'    => 'Select budget range',
                'is_required'    => false,
                'show_in_list'   => true,
                'show_in_filter' => true,
                'sort_order'     => 1,
            ],
            [
                'label'          => 'Lead Score',
                'field_key'      => 'lead_score',
                'field_type'     => 'number',
                'options'        => null,
                'placeholder'    => '0 - 100',
                'is_required'    => false,
                'show_in_list'   => true,
                'show_in_filter' => false,
                'sort_order'     => 2,
            ],
            [
                'label'          => 'Product Interest',
                'field_key'      => 'product_interest',
                'field_type'     => 'multi_select',
                'options'        => ['Software', 'Hardware', 'Services', 'Consulting', 'Support', 'Training'],
                'placeholder'    => null,
                'is_required'    => false,
                'show_in_list'   => false,
                'show_in_filter' => true,
                'sort_order'     => 3,
            ],
            [
                'label'          => 'Decision Timeline',
                'field_key'      => 'decision_timeline',
                'field_type'     => 'dropdown',
                'options'        => ['Immediate', 'Within 1 Month', '1-3 Months', '3-6 Months', 'Over 6 Months'],
                'placeholder'    => 'When will they decide?',
                'is_required'    => false,
                'show_in_list'   => false,
                'show_in_filter' => true,
                'sort_order'     => 4,
            ],
            [
                'label'          => 'Company Size',
                'field_key'      => 'company_size',
                'field_type'     => 'dropdown',
                'options'        => ['1-10 Employees', '11-50 Employees', '51-200 Employees', '201-500 Employees', '500+ Employees'],
                'placeholder'    => 'Select company size',
                'is_required'    => false,
                'show_in_list'   => false,
                'show_in_filter' => true,
                'sort_order'     => 5,
            ],
            [
                'label'          => 'Competitor',
                'field_key'      => 'competitor',
                'field_type'     => 'text',
                'options'        => null,
                'placeholder'    => 'Which competitor are they using?',
                'is_required'    => false,
                'show_in_list'   => false,
                'show_in_filter' => false,
                'sort_order'     => 6,
            ],
            [
                'label'          => 'Hot Lead',
                'field_key'      => 'is_hot_lead',
                'field_type'     => 'checkbox',
                'options'        => null,
                'placeholder'    => 'Mark as hot lead',
                'is_required'    => false,
                'show_in_list'   => true,
                'show_in_filter' => true,
                'sort_order'     => 7,
            ],
            [
                'label'          => 'Additional Notes',
                'field_key'      => 'additional_notes',
                'field_type'     => 'textarea',
                'options'        => null,
                'placeholder'    => 'Any additional information...',
                'is_required'    => false,
                'show_in_list'   => false,
                'show_in_filter' => false,
                'sort_order'     => 8,
            ],
        ];

        $this->createFields($tenantId, 'lead', $fields);
    }

    // ── Contact Fields ────────────────────────────────────────────
    private function seedContactFields(int $tenantId): void
    {
        $fields = [
            [
                'label'          => 'LinkedIn Profile',
                'field_key'      => 'linkedin_profile',
                'field_type'     => 'url',
                'options'        => null,
                'placeholder'    => 'https://linkedin.com/in/username',
                'is_required'    => false,
                'show_in_list'   => false,
                'show_in_filter' => false,
                'sort_order'     => 1,
            ],
            [
                'label'          => 'Date of Birth',
                'field_key'      => 'date_of_birth',
                'field_type'     => 'date',
                'options'        => null,
                'placeholder'    => null,
                'is_required'    => false,
                'show_in_list'   => false,
                'show_in_filter' => false,
                'sort_order'     => 2,
            ],
            [
                'label'          => 'Industry',
                'field_key'      => 'industry',
                'field_type'     => 'dropdown',
                'options'        => ['IT & Software', 'Manufacturing', 'Healthcare', 'Finance', 'Education', 'Retail', 'Real Estate', 'Construction', 'FMCG', 'Hospitality', 'Other'],
                'placeholder'    => 'Select industry',
                'is_required'    => false,
                'show_in_list'   => true,
                'show_in_filter' => true,
                'sort_order'     => 3,
            ],
            [
                'label'          => 'Annual Turnover',
                'field_key'      => 'annual_turnover',
                'field_type'     => 'dropdown',
                'options'        => ['Under ₹10L', '₹10L - ₹1Cr', '₹1Cr - ₹10Cr', '₹10Cr - ₹100Cr', 'Above ₹100Cr'],
                'placeholder'    => 'Select turnover range',
                'is_required'    => false,
                'show_in_list'   => false,
                'show_in_filter' => true,
                'sort_order'     => 4,
            ],
            [
                'label'          => 'Secondary Phone',
                'field_key'      => 'secondary_phone',
                'field_type'     => 'phone',
                'options'        => null,
                'placeholder'    => '+91 98765 43210',
                'is_required'    => false,
                'show_in_list'   => false,
                'show_in_filter' => false,
                'sort_order'     => 5,
            ],
            [
                'label'          => 'VIP Customer',
                'field_key'      => 'is_vip',
                'field_type'     => 'checkbox',
                'options'        => null,
                'placeholder'    => 'Mark as VIP customer',
                'is_required'    => false,
                'show_in_list'   => true,
                'show_in_filter' => true,
                'sort_order'     => 6,
            ],
        ];

        $this->createFields($tenantId, 'contact', $fields);
    }

    // ── Deal Fields ───────────────────────────────────────────────
    private function seedDealFields(int $tenantId): void
    {
        $fields = [
            [
                'label'          => 'Deal Type',
                'field_key'      => 'deal_type',
                'field_type'     => 'dropdown',
                'options'        => ['New Business', 'Renewal', 'Upsell', 'Cross-sell', 'Partnership'],
                'placeholder'    => 'Select deal type',
                'is_required'    => false,
                'show_in_list'   => true,
                'show_in_filter' => true,
                'sort_order'     => 1,
            ],
            [
                'label'          => 'Product / Service',
                'field_key'      => 'product_service',
                'field_type'     => 'multi_select',
                'options'        => ['Product A', 'Product B', 'Service X', 'Service Y', 'Annual Maintenance', 'Training'],
                'placeholder'    => null,
                'is_required'    => false,
                'show_in_list'   => false,
                'show_in_filter' => true,
                'sort_order'     => 2,
            ],
            [
                'label'          => 'Payment Terms',
                'field_key'      => 'payment_terms',
                'field_type'     => 'dropdown',
                'options'        => ['Immediate', 'Net 15', 'Net 30', 'Net 45', 'Net 60', 'Quarterly', 'Annually'],
                'placeholder'    => 'Select payment terms',
                'is_required'    => false,
                'show_in_list'   => false,
                'show_in_filter' => false,
                'sort_order'     => 3,
            ],
            [
                'label'          => 'Contract Start Date',
                'field_key'      => 'contract_start_date',
                'field_type'     => 'date',
                'options'        => null,
                'placeholder'    => null,
                'is_required'    => false,
                'show_in_list'   => false,
                'show_in_filter' => false,
                'sort_order'     => 4,
            ],
            [
                'label'          => 'Contract End Date',
                'field_key'      => 'contract_end_date',
                'field_type'     => 'date',
                'options'        => null,
                'placeholder'    => null,
                'is_required'    => false,
                'show_in_list'   => false,
                'show_in_filter' => false,
                'sort_order'     => 5,
            ],
            [
                'label'          => 'Recurring Revenue',
                'field_key'      => 'is_recurring',
                'field_type'     => 'checkbox',
                'options'        => null,
                'placeholder'    => 'This is a recurring deal',
                'is_required'    => false,
                'show_in_list'   => true,
                'show_in_filter' => true,
                'sort_order'     => 6,
            ],
            [
                'label'          => 'Competitor Involved',
                'field_key'      => 'competitor_involved',
                'field_type'     => 'text',
                'options'        => null,
                'placeholder'    => 'Which competitor is bidding?',
                'is_required'    => false,
                'show_in_list'   => false,
                'show_in_filter' => false,
                'sort_order'     => 7,
            ],
        ];

        $this->createFields($tenantId, 'deal', $fields);
    }

    // ── Quotation Fields ──────────────────────────────────────────
    private function seedQuotationFields(int $tenantId): void
    {
        $fields = [
            [
                'label'          => 'Delivery Timeline',
                'field_key'      => 'delivery_timeline',
                'field_type'     => 'dropdown',
                'options'        => ['Immediate', '1-2 Weeks', '1 Month', '2-3 Months', 'Custom'],
                'placeholder'    => 'Select delivery timeline',
                'is_required'    => false,
                'show_in_list'   => false,
                'show_in_filter' => false,
                'sort_order'     => 1,
            ],
            [
                'label'          => 'Shipping Address',
                'field_key'      => 'shipping_address',
                'field_type'     => 'textarea',
                'options'        => null,
                'placeholder'    => 'Enter shipping address if different...',
                'is_required'    => false,
                'show_in_list'   => false,
                'show_in_filter' => false,
                'sort_order'     => 2,
            ],
            [
                'label'          => 'PO Number',
                'field_key'      => 'po_number',
                'field_type'     => 'text',
                'options'        => null,
                'placeholder'    => 'Purchase Order Number',
                'is_required'    => false,
                'show_in_list'   => true,
                'show_in_filter' => false,
                'sort_order'     => 3,
            ],
            [
                'label'          => 'Installation Required',
                'field_key'      => 'installation_required',
                'field_type'     => 'checkbox',
                'options'        => null,
                'placeholder'    => 'Installation / Setup required',
                'is_required'    => false,
                'show_in_list'   => false,
                'show_in_filter' => false,
                'sort_order'     => 4,
            ],
        ];

        $this->createFields($tenantId, 'quotation', $fields);
    }

    // ── Task Fields ───────────────────────────────────────────────
    private function seedTaskFields(int $tenantId): void
    {
        $fields = [
            [
                'label'          => 'Task Category',
                'field_key'      => 'task_category',
                'field_type'     => 'dropdown',
                'options'        => ['Sales', 'Support', 'Marketing', 'Admin', 'Development', 'Follow-up', 'Meeting', 'Other'],
                'placeholder'    => 'Select category',
                'is_required'    => false,
                'show_in_list'   => true,
                'show_in_filter' => true,
                'sort_order'     => 1,
            ],
            [
                'label'          => 'Estimated Hours',
                'field_key'      => 'estimated_hours',
                'field_type'     => 'number',
                'options'        => null,
                'placeholder'    => '0',
                'is_required'    => false,
                'show_in_list'   => false,
                'show_in_filter' => false,
                'sort_order'     => 2,
            ],
            [
                'label'          => 'Billable',
                'field_key'      => 'is_billable',
                'field_type'     => 'checkbox',
                'options'        => null,
                'placeholder'    => 'This task is billable',
                'is_required'    => false,
                'show_in_list'   => true,
                'show_in_filter' => true,
                'sort_order'     => 3,
            ],
            [
                'label'          => 'Related URL',
                'field_key'      => 'related_url',
                'field_type'     => 'url',
                'options'        => null,
                'placeholder'    => 'https://...',
                'is_required'    => false,
                'show_in_list'   => false,
                'show_in_filter' => false,
                'sort_order'     => 4,
            ],
        ];

        $this->createFields($tenantId, 'task', $fields);
    }

    // ── Helper — create fields ────────────────────────────────────
    private function createFields(int $tenantId, string $module, array $fields): void
    {
        foreach ($fields as $f) {
            CustomField::firstOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'module'    => $module,
                    'field_key' => $f['field_key'],
                ],
                array_merge($f, [
                    'tenant_id' => $tenantId,
                    'module'    => $module,
                    'is_active' => true,
                ])
            );
        }
    }
}