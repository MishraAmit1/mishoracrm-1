<?php

namespace Database\Seeders;

use App\Models\TaskTemplate;
use App\Models\User;
use Illuminate\Database\Seeder;

class TaskTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = User::where('user_type', 'tenant_admin')->first();
        if (!$tenant) {
            $this->command->warn('No tenant_admin found. Run SuperAdminSeeder first.');
            return;
        }

        $tenantId = $tenant->tenant_id;

        $templates = [
            [
                'name'             => 'New Lead Onboarding',
                'description'      => 'Standard onboarding checklist for a freshly captured lead.',
                'default_priority' => 'high',
                'default_tags'     => ['onboarding', 'lead'],
                'checklist_items'  => [
                    'Verify contact details',
                    'Send welcome email',
                    'Schedule intro call',
                    'Share product brochure',
                    'Log first response time',
                ],
            ],
            [
                'name'             => 'Deal Closing Checklist',
                'description'      => 'Final steps to move a deal from negotiation to won.',
                'default_priority' => 'high',
                'default_tags'     => ['deal', 'closing'],
                'checklist_items'  => [
                    'Confirm final pricing with client',
                    'Get legal review of contract',
                    'Collect advance payment',
                    'Send signed agreement copy',
                    'Update deal stage to Won',
                ],
            ],
            [
                'name'             => 'Client Onboarding (Post-Sale)',
                'description'      => 'Kickoff checklist once a deal is won and the client is handed off to delivery.',
                'default_priority' => 'medium',
                'default_tags'     => ['onboarding', 'client'],
                'checklist_items'  => [
                    'Schedule kickoff call',
                    'Share onboarding documents',
                    'Set up client in delivery system',
                    'Introduce assigned account manager',
                    'Confirm first milestone timeline',
                ],
            ],
            [
                'name'             => 'Weekly Team Report',
                'description'      => 'Recurring checklist for compiling and sending the weekly status report.',
                'default_priority' => 'low',
                'default_tags'     => ['reporting', 'weekly'],
                'checklist_items'  => [
                    'Pull pipeline numbers',
                    'Summarize wins and losses',
                    'List blockers from team',
                    'Send report to management',
                ],
            ],
            [
                'name'             => 'Contract Renewal',
                'description'      => 'Checklist to run through before a client contract expires.',
                'default_priority' => 'high',
                'default_tags'     => ['renewal', 'client'],
                'checklist_items'  => [
                    'Review current contract terms',
                    'Check client usage and satisfaction',
                    'Prepare renewal proposal',
                    'Schedule renewal call',
                    'Send updated contract for signature',
                ],
            ],
            [
                'name'             => 'Product Demo Follow-up',
                'description'      => 'Checklist for the 48 hours after a product demo call.',
                'default_priority' => 'medium',
                'default_tags'     => ['demo', 'follow-up'],
                'checklist_items'  => [
                    'Send thank-you email with recording',
                    'Share pricing sheet',
                    'Answer open questions from the call',
                    'Schedule follow-up call',
                ],
            ],
        ];

        foreach ($templates as $data) {
            TaskTemplate::updateOrCreate(
                ['tenant_id' => $tenantId, 'name' => $data['name']],
                [
                    'description'      => $data['description'],
                    'default_priority' => $data['default_priority'],
                    'default_tags'     => $data['default_tags'],
                    'checklist_items'  => $data['checklist_items'],
                    'created_by'       => $tenant->id,
                ]
            );
        }

        $this->command->info('TaskTemplateSeeder: ' . count($templates) . ' task templates created.');
    }
}
