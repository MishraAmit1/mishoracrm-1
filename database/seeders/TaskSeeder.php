<?php

namespace Database\Seeders;

use App\Models\Deal;
use App\Models\Lead;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;

class TaskSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = User::where('user_type', 'tenant_admin')->first();
        if (!$tenant) {
            $this->command->warn('No tenant_admin found. Run SuperAdminSeeder first.');
            return;
        }

        $tenantId  = $tenant->tenant_id;

        $staff = User::where('tenant_id', $tenantId)
            ->where('user_type', '!=', 'superadmin')
            ->pluck('id')
            ->toArray();

        $assignees = count($staff) ? $staff : [$tenant->id];

        $leads = Lead::where('tenant_id', $tenantId)->pluck('id')->toArray();
        $deals = Deal::where('tenant_id', $tenantId)->pluck('id')->toArray();

        $tasks = [
            // Lead-linked tasks
            ['title' => 'Call back regarding product demo',        'description' => 'Client asked for a callback to discuss demo schedule.',          'status' => 'pending',     'priority' => 'high',   'due_at' => now()->addDays(1),   'taskable_type' => Lead::class, 'taskable_index' => 0],
            ['title' => 'Send product brochure via email',         'description' => 'Share latest brochure and pricing sheet.',                        'status' => 'pending',     'priority' => 'medium', 'due_at' => now()->addDays(2),   'taskable_type' => Lead::class, 'taskable_index' => 1],
            ['title' => 'Follow up on WhatsApp',                   'description' => 'No response to last email — try WhatsApp.',                       'status' => 'pending',     'priority' => 'medium', 'due_at' => now()->addDays(1),   'taskable_type' => Lead::class, 'taskable_index' => 2],
            ['title' => 'Schedule site visit',                     'description' => 'Client wants to visit office before finalising.',                  'status' => 'in_progress', 'priority' => 'high',   'due_at' => now()->addDays(3),   'taskable_type' => Lead::class, 'taskable_index' => 3],
            ['title' => 'Prepare custom pricing quote',            'description' => 'Client needs discount on bulk order — prepare revised quote.',     'status' => 'pending',     'priority' => 'high',   'due_at' => now()->addDays(2),   'taskable_type' => Lead::class, 'taskable_index' => 4],
            ['title' => 'Verify contact details',                  'description' => 'Phone number seems incorrect — verify before calling.',            'status' => 'completed',   'priority' => 'low',    'due_at' => now()->subDays(1),   'taskable_type' => Lead::class, 'taskable_index' => 0, 'completed_at' => now()->subDays(1)],
            ['title' => 'Send onboarding welcome email',           'description' => 'Lead converted — send welcome kit and next steps.',                'status' => 'completed',   'priority' => 'medium', 'due_at' => now()->subDays(2),   'taskable_type' => Lead::class, 'taskable_index' => 1, 'completed_at' => now()->subDays(2)],

            // Deal-linked tasks
            ['title' => 'Draft and send proposal document',        'description' => 'Prepare detailed proposal with timelines and deliverables.',       'status' => 'in_progress', 'priority' => 'high',   'due_at' => now()->addDays(2),   'taskable_type' => Deal::class, 'taskable_index' => 0],
            ['title' => 'Get legal review of contract',            'description' => 'Share draft contract with legal team for review.',                 'status' => 'pending',     'priority' => 'high',   'due_at' => now()->addDays(4),   'taskable_type' => Deal::class, 'taskable_index' => 1],
            ['title' => 'Collect 50% advance payment',             'description' => 'Send payment link and confirm receipt.',                           'status' => 'pending',     'priority' => 'high',   'due_at' => now()->addDays(3),   'taskable_type' => Deal::class, 'taskable_index' => 2],
            ['title' => 'Kickoff meeting with client',             'description' => 'Schedule and conduct project kickoff call.',                       'status' => 'pending',     'priority' => 'medium', 'due_at' => now()->addDays(5),   'taskable_type' => Deal::class, 'taskable_index' => 3],
            ['title' => 'Share project timeline on email',         'description' => 'Send confirmed Gantt chart and milestone dates.',                  'status' => 'in_progress', 'priority' => 'medium', 'due_at' => now()->addDays(2),   'taskable_type' => Deal::class, 'taskable_index' => 4],
            ['title' => 'Follow up on pending approval',           'description' => 'Awaiting sign-off from client management.',                        'status' => 'pending',     'priority' => 'high',   'due_at' => now()->addDays(1),   'taskable_type' => Deal::class, 'taskable_index' => 5],
            ['title' => 'Negotiate final payment terms',           'description' => 'Client wants 60-day credit — discuss with finance team.',          'status' => 'in_progress', 'priority' => 'high',   'due_at' => now()->addDays(2),   'taskable_type' => Deal::class, 'taskable_index' => 6],
            ['title' => 'Conduct post-sale satisfaction call',     'description' => 'Check in with client 1 week after contract signing.',              'status' => 'completed',   'priority' => 'low',    'due_at' => now()->subDays(3),   'taskable_type' => Deal::class, 'taskable_index' => 9, 'completed_at' => now()->subDays(3)],
            ['title' => 'Upload signed contract to CRM',           'description' => 'Scan and attach signed agreement to deal record.',                 'status' => 'completed',   'priority' => 'medium', 'due_at' => now()->subDays(4),   'taskable_type' => Deal::class, 'taskable_index' => 10, 'completed_at' => now()->subDays(4)],
            ['title' => 'Issue invoice for first milestone',       'description' => 'Generate and send invoice for Phase 1 completion.',                'status' => 'cancelled',     'priority' => 'high',   'due_at' => now()->subDays(2),   'taskable_type' => Deal::class, 'taskable_index' => 11],
            ['title' => 'Collect final payment',                   'description' => 'Project complete — send final invoice and follow up.',             'status' => 'cancelled',     'priority' => 'high',   'due_at' => now()->subDays(5),   'taskable_type' => Deal::class, 'taskable_index' => 12],
        ];

        foreach ($tasks as $i => $data) {
            $type  = $data['taskable_type'];
            $index = $data['taskable_index'];

            $pool     = $type === Lead::class ? $leads : $deals;
            $modelId  = count($pool) ? $pool[$index % count($pool)] : null;

            Task::create([
                'tenant_id'     => $tenantId,
                'title'         => $data['title'],
                'description'   => $data['description'],
                'status'        => $data['status'],
                'priority'      => $data['priority'],
                'due_at'        => $data['due_at'],
                'completed_at'  => $data['completed_at'] ?? null,
                'assigned_to'   => $assignees[$i % count($assignees)],
                'taskable_type' => $type,
                'taskable_id'   => $modelId,
            ]);
        }

        $this->command->info('TaskSeeder: ' . count($tasks) . ' tasks created.');
    }
}
