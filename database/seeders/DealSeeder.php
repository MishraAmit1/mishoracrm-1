<?php

namespace Database\Seeders;

use App\Models\Contact;
use App\Models\Deal;
use App\Models\User;
use Illuminate\Database\Seeder;

class DealSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = User::where('user_type', 'tenant_admin')->first();
        if (!$tenant) {
            $this->command->warn('No tenant_admin found. Run SuperAdminSeeder first.');
            return;
        }

        $tenantId  = $tenant->tenant_id;
        $createdBy = $tenant->id;

        $staff = User::where('tenant_id', $tenantId)
            ->where('user_type', '!=', 'superadmin')
            ->pluck('id')
            ->toArray();

        $assignees = count($staff) ? $staff : [$createdBy];

        $contacts = Contact::where('tenant_id', $tenantId)->pluck('id')->toArray();
        $contactId = fn(int $i) => count($contacts) ? $contacts[$i % count($contacts)] : null;

        $deals = [
            // New
            ['title' => 'Website Redesign Project',        'value' => 150000,  'stage' => 'new',         'probability' => 10,  'expected_close_date' => now()->addDays(30), 'notes' => 'Client wants full redesign with CMS.'],
            ['title' => 'Annual IT Support Contract',       'value' => 480000,  'stage' => 'new',         'probability' => 10,  'expected_close_date' => now()->addDays(45), 'notes' => 'Renewal discussion ongoing.'],
            ['title' => 'Mobile App Development',           'value' => 750000,  'stage' => 'new',         'probability' => 15,  'expected_close_date' => now()->addDays(60), 'notes' => 'Android + iOS both required.'],

            // Proposal
            ['title' => 'ERP Implementation',               'value' => 1800000, 'stage' => 'proposal',    'probability' => 30,  'expected_close_date' => now()->addDays(25), 'notes' => 'Demo done, awaiting proposal approval.'],
            ['title' => 'Digital Marketing Retainer',       'value' => 96000,   'stage' => 'proposal',    'probability' => 35,  'expected_close_date' => now()->addDays(15), 'notes' => 'SEO + Social Media 6-month package.'],
            ['title' => 'Cloud Migration Services',         'value' => 620000,  'stage' => 'proposal',    'probability' => 25,  'expected_close_date' => now()->addDays(40), 'notes' => 'AWS migration for 3 servers.'],

            // Negotiation
            ['title' => 'CRM Customization',                'value' => 320000,  'stage' => 'negotiation', 'probability' => 60,  'expected_close_date' => now()->addDays(10), 'notes' => 'Negotiating on payment terms.'],
            ['title' => 'Security Audit & Compliance',      'value' => 210000,  'stage' => 'negotiation', 'probability' => 65,  'expected_close_date' => now()->addDays(8),  'notes' => 'Final price discussion pending.'],
            ['title' => 'Staff Training Program',           'value' => 85000,   'stage' => 'negotiation', 'probability' => 70,  'expected_close_date' => now()->addDays(12), 'notes' => '2-day workshop for 20 staff.'],

            // Won
            ['title' => 'E-commerce Platform Setup',        'value' => 550000,  'stage' => 'won',         'probability' => 100, 'expected_close_date' => now()->subDays(5),  'actual_close_date' => now()->subDays(5),  'notes' => 'Contract signed. Kickoff next Monday.'],
            ['title' => 'SaaS Subscription — Pro Plan',     'value' => 120000,  'stage' => 'won',         'probability' => 100, 'expected_close_date' => now()->subDays(10), 'actual_close_date' => now()->subDays(10), 'notes' => 'Annual plan — auto-renewal enabled.'],
            ['title' => 'Accounting Software License',      'value' => 75000,   'stage' => 'won',         'probability' => 100, 'expected_close_date' => now()->subDays(3),  'actual_close_date' => now()->subDays(3),  'notes' => '5 user license purchased.'],

            // Lost
            ['title' => 'Bulk SMS Gateway Integration',     'value' => 45000,   'stage' => 'lost',        'probability' => 0,   'expected_close_date' => now()->subDays(15), 'actual_close_date' => now()->subDays(15), 'lost_reason' => 'Client chose a cheaper vendor.'],
            ['title' => 'HR Management System',             'value' => 390000,  'stage' => 'lost',        'probability' => 0,   'expected_close_date' => now()->subDays(8),  'actual_close_date' => now()->subDays(8),  'lost_reason' => 'Budget cut — project postponed indefinitely.'],
        ];

        foreach ($deals as $i => $data) {
            Deal::create(array_merge($data, [
                'tenant_id'  => $tenantId,
                'created_by' => $createdBy,
                'assigned_to' => $assignees[$i % count($assignees)],
                'contact_id'  => $contactId($i),
            ]));
        }

        $this->command->info('DealSeeder: ' . count($deals) . ' deals created.');
    }
}
