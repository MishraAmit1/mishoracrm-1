<?php

namespace Database\Seeders;

use App\Models\GlobalFieldTemplate;
use Illuminate\Database\Seeder;

class GlobalFieldTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [

            // ── Lead ──────────────────────────────────────────────
            ['module'=>'lead','label'=>'Budget Range',     'field_key'=>'budget_range',    'field_type'=>'dropdown',  'options'=>['Under ₹50K','₹50K–₹1L','₹1L–₹5L','₹5L–₹10L','Above ₹10L'], 'is_system'=>false,'is_recommended'=>true, 'sort_order'=>1],
            ['module'=>'lead','label'=>'Lead Score',       'field_key'=>'lead_score',      'field_type'=>'number',    'options'=>null, 'placeholder'=>'0–100', 'is_system'=>false,'is_recommended'=>true, 'sort_order'=>2],
            ['module'=>'lead','label'=>'Product Interest', 'field_key'=>'product_interest','field_type'=>'multi_select','options'=>['Software','Hardware','Services','Consulting','Training'],'is_system'=>false,'is_recommended'=>false,'sort_order'=>3],
            ['module'=>'lead','label'=>'Decision Timeline','field_key'=>'decision_timeline','field_type'=>'dropdown', 'options'=>['Immediate','1 Month','1–3 Months','3–6 Months','6+ Months'],'is_system'=>false,'is_recommended'=>true,'sort_order'=>4],
            ['module'=>'lead','label'=>'Company Size',     'field_key'=>'company_size',    'field_type'=>'dropdown',  'options'=>['1–10','11–50','51–200','201–500','500+'],'is_system'=>false,'is_recommended'=>false,'sort_order'=>5],
            ['module'=>'lead','label'=>'Hot Lead',         'field_key'=>'is_hot_lead',     'field_type'=>'checkbox',  'options'=>null, 'placeholder'=>'Mark as hot lead', 'is_system'=>false,'is_recommended'=>true,'sort_order'=>6],
            ['module'=>'lead','label'=>'Competitor',       'field_key'=>'competitor',      'field_type'=>'text',      'options'=>null, 'is_system'=>false,'is_recommended'=>false,'sort_order'=>7],
            ['module'=>'lead','label'=>'Referred By',      'field_key'=>'referred_by',     'field_type'=>'text',      'options'=>null, 'placeholder'=>'Referrer name','is_system'=>false,'is_recommended'=>false,'sort_order'=>8],

            // ── Contact ───────────────────────────────────────────
            ['module'=>'contact','label'=>'LinkedIn Profile','field_key'=>'linkedin',     'field_type'=>'url',       'options'=>null,'is_system'=>false,'is_recommended'=>true,'sort_order'=>1],
            ['module'=>'contact','label'=>'Date of Birth',  'field_key'=>'dob',           'field_type'=>'date',      'options'=>null,'is_system'=>false,'is_recommended'=>false,'sort_order'=>2],
            ['module'=>'contact','label'=>'Industry',       'field_key'=>'industry',      'field_type'=>'dropdown',  'options'=>['IT & Software','Manufacturing','Healthcare','Finance','Education','Retail','Real Estate','Construction','FMCG','Other'],'is_system'=>false,'is_recommended'=>true,'sort_order'=>3],
            ['module'=>'contact','label'=>'Annual Turnover','field_key'=>'turnover',      'field_type'=>'dropdown',  'options'=>['Under ₹10L','₹10L–₹1Cr','₹1Cr–₹10Cr','₹10Cr–₹100Cr','Above ₹100Cr'],'is_system'=>false,'is_recommended'=>false,'sort_order'=>4],
            ['module'=>'contact','label'=>'VIP Customer',   'field_key'=>'is_vip',        'field_type'=>'checkbox',  'options'=>null,'placeholder'=>'Mark as VIP','is_system'=>false,'is_recommended'=>true,'sort_order'=>5],

            // ── Deal ──────────────────────────────────────────────
            ['module'=>'deal','label'=>'Deal Type',        'field_key'=>'deal_type',      'field_type'=>'dropdown',  'options'=>['New Business','Renewal','Upsell','Cross-sell','Partnership'],'is_system'=>false,'is_recommended'=>true,'sort_order'=>1],
            ['module'=>'deal','label'=>'Payment Terms',    'field_key'=>'payment_terms',  'field_type'=>'dropdown',  'options'=>['Immediate','Net 15','Net 30','Net 60','Quarterly'],'is_system'=>false,'is_recommended'=>true,'sort_order'=>2],
            ['module'=>'deal','label'=>'Contract End Date','field_key'=>'contract_end',   'field_type'=>'date',      'options'=>null,'is_system'=>false,'is_recommended'=>false,'sort_order'=>3],
            ['module'=>'deal','label'=>'Recurring Revenue','field_key'=>'is_recurring',   'field_type'=>'checkbox',  'options'=>null,'placeholder'=>'Recurring deal','is_system'=>false,'is_recommended'=>true,'sort_order'=>4],

            // ── Quotation ─────────────────────────────────────────
            ['module'=>'quotation','label'=>'PO Number',         'field_key'=>'po_number',       'field_type'=>'text',  'options'=>null,'is_system'=>false,'is_recommended'=>true,'sort_order'=>1],
            ['module'=>'quotation','label'=>'Delivery Timeline',  'field_key'=>'delivery_timeline','field_type'=>'dropdown','options'=>['Immediate','1–2 Weeks','1 Month','2–3 Months','Custom'],'is_system'=>false,'is_recommended'=>true,'sort_order'=>2],
            ['module'=>'quotation','label'=>'Installation Required','field_key'=>'installation',  'field_type'=>'checkbox','options'=>null,'is_system'=>false,'is_recommended'=>false,'sort_order'=>3],

            // ── Task ──────────────────────────────────────────────
            ['module'=>'task','label'=>'Task Category',    'field_key'=>'task_category',  'field_type'=>'dropdown',  'options'=>['Sales','Support','Marketing','Admin','Development','Meeting','Other'],'is_system'=>false,'is_recommended'=>true,'sort_order'=>1],
            ['module'=>'task','label'=>'Estimated Hours',  'field_key'=>'est_hours',      'field_type'=>'number',    'options'=>null,'placeholder'=>'0','is_system'=>false,'is_recommended'=>false,'sort_order'=>2],
            ['module'=>'task','label'=>'Billable',         'field_key'=>'is_billable',    'field_type'=>'checkbox',  'options'=>null,'placeholder'=>'This task is billable','is_system'=>false,'is_recommended'=>true,'sort_order'=>3],
        ];

        foreach ($templates as $t) {
            GlobalFieldTemplate::updateOrCreate(
                ['module' => $t['module'], 'field_key' => $t['field_key']],
                $t
            );
        }

        $this->command->info('Global field templates seeded: ' . count($templates));
    }
}