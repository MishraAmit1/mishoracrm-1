<?php

namespace Database\Seeders;

use App\Models\Notification;
use App\Models\NotificationPreference;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;

class NotificationSeeder extends Seeder
{
    public function run(): void
    {
        $tenants = Tenant::all();

        if ($tenants->isEmpty()) {
            $this->command->warn('No tenants found. Run TenantSeeder first.');
            return;
        }

        foreach ($tenants as $tenant) {
            $users = User::withoutGlobalScopes()
                ->where('tenant_id', $tenant->id)
                ->where('is_active', true)
                ->get();

            if ($users->isEmpty()) continue;

            // ── Default preferences for all users ────────────────
            $this->seedPreferences($tenant->id, $users);

            // ── Sample notifications for first user ───────────────
            $firstUser = $users->first();
            $this->seedNotifications($tenant->id, $firstUser->id);
        }

        $this->command->info('Notifications seeded successfully.');
    }

    // ── Default preferences ───────────────────────────────────────
    private function seedPreferences(int $tenantId, $users): void
    {
        $types    = config('notifications.types', []);
        $channels = config('notifications.channels', []);

        foreach ($users as $user) {
            foreach ($types as $type => $cfg) {
                NotificationPreference::firstOrCreate(
                    [
                        'tenant_id' => $tenantId,
                        'user_id'   => $user->id,
                        'type'      => $type,
                    ],
                    [
                        'in_app'   => true,
                        'email'    => in_array('email',    $cfg['channels'] ?? []),
                        'whatsapp' => in_array('whatsapp', $cfg['channels'] ?? []),
                        'slack'    => false,
                    ]
                );
            }
        }
    }

    // ── Sample notifications ──────────────────────────────────────
    private function seedNotifications(int $tenantId, int $userId): void
    {
        $notifications = [

            // ── Leads ─────────────────────────────────────────────
            [
                'type'    => 'lead.created',
                'title'   => 'New Lead Added',
                'message' => 'New lead Priya Mehta added from Facebook Ads',
                'icon'    => 'user-plus',
                'color'   => 'accent',
                'url'     => '/leads',
                'is_read' => false,
                'created_at' => now()->subMinutes(5),
            ],
            [
                'type'    => 'lead.assigned',
                'title'   => 'Lead Assigned to You',
                'message' => 'Lead Amit Sharma assigned to you by Manager',
                'icon'    => 'user-check',
                'color'   => 'accent',
                'url'     => '/leads',
                'is_read' => false,
                'created_at' => now()->subMinutes(15),
            ],
            [
                'type'    => 'lead.converted',
                'title'   => 'Lead Converted',
                'message' => 'Lead Sunita Patel converted to contact successfully',
                'icon'    => 'check-circle',
                'color'   => 'green',
                'url'     => '/contacts',
                'is_read' => false,
                'created_at' => now()->subMinutes(30),
            ],
            [
                'type'    => 'lead.status_changed',
                'title'   => 'Lead Status Changed',
                'message' => 'Lead Vikram Joshi moved to Qualified',
                'icon'    => 'refresh',
                'color'   => 'amber',
                'url'     => '/leads',
                'is_read' => true,
                'read_at' => now()->subMinutes(10),
                'created_at' => now()->subHour(),
            ],

            // ── Deals ─────────────────────────────────────────────
            [
                'type'    => 'deal.won',
                'title'   => 'Deal Won! 🎉',
                'message' => '🎉 Deal "Office Furniture Supply" marked as Won! ₹1,24,000',
                'icon'    => 'trophy',
                'color'   => 'green',
                'url'     => '/deals',
                'is_read' => false,
                'created_at' => now()->subHours(2),
            ],
            [
                'type'    => 'deal.stage_changed',
                'title'   => 'Deal Stage Updated',
                'message' => 'Deal "Laptop Procurement" moved to Negotiation',
                'icon'    => 'arrow-right',
                'color'   => 'amber',
                'url'     => '/deals',
                'is_read' => false,
                'created_at' => now()->subHours(3),
            ],
            [
                'type'    => 'deal.created',
                'title'   => 'New Deal Created',
                'message' => 'New deal "Software License 2024" created worth ₹84,000',
                'icon'    => 'briefcase',
                'color'   => 'accent',
                'url'     => '/deals',
                'is_read' => true,
                'read_at' => now()->subHours(2),
                'created_at' => now()->subHours(4),
            ],
            [
                'type'    => 'deal.lost',
                'title'   => 'Deal Lost',
                'message' => 'Deal "Annual Maintenance" marked as Lost',
                'icon'    => 'x-circle',
                'color'   => 'red',
                'url'     => '/deals',
                'is_read' => true,
                'read_at' => now()->subHours(1),
                'created_at' => now()->subHours(5),
            ],

            // ── Follow-ups ────────────────────────────────────────
            [
                'type'    => 'followup.due',
                'title'   => 'Follow-up Due Now',
                'message' => 'Follow-up with Deepa Gupta is due now',
                'icon'    => 'clock',
                'color'   => 'amber',
                'url'     => '/followups',
                'is_read' => false,
                'created_at' => now()->subMinutes(2),
            ],
            [
                'type'    => 'followup.overdue',
                'title'   => 'Follow-up Overdue!',
                'message' => 'Follow-up with Kiran Reddy is overdue!',
                'icon'    => 'alert-circle',
                'color'   => 'red',
                'url'     => '/followups',
                'is_read' => false,
                'created_at' => now()->subHours(1),
            ],
            [
                'type'    => 'followup.scheduled',
                'title'   => 'Follow-up Scheduled',
                'message' => 'Follow-up with Rahul Verma scheduled for tomorrow 10:00 AM',
                'icon'    => 'calendar',
                'color'   => 'accent',
                'url'     => '/followups',
                'is_read' => true,
                'read_at' => now()->subHours(3),
                'created_at' => now()->subHours(6),
            ],

            // ── Tasks ─────────────────────────────────────────────
            [
                'type'    => 'task.assigned',
                'title'   => 'Task Assigned to You',
                'message' => 'Task "Send quotation to Joshi Enterprises" assigned to you',
                'icon'    => 'check-square',
                'color'   => 'accent',
                'url'     => '/tasks',
                'is_read' => false,
                'created_at' => now()->subHours(2),
            ],
            [
                'type'    => 'task.due',
                'title'   => 'Task Due Today',
                'message' => 'Task "Update CRM records" is due today',
                'icon'    => 'clock',
                'color'   => 'amber',
                'url'     => '/tasks',
                'is_read' => true,
                'read_at' => now()->subHours(1),
                'created_at' => now()->subHours(7),
            ],
            [
                'type'    => 'task.completed',
                'title'   => 'Task Completed',
                'message' => 'Task "Team standup meeting" marked as completed',
                'icon'    => 'check',
                'color'   => 'green',
                'url'     => '/tasks',
                'is_read' => true,
                'read_at' => now()->subHours(4),
                'created_at' => now()->subHours(8),
            ],

            // ── Quotations ────────────────────────────────────────
            [
                'type'    => 'quotation.accepted',
                'title'   => 'Quotation Accepted ✅',
                'message' => '✅ Quotation QT-20240115-0012 accepted by Sharma Enterprises',
                'icon'    => 'thumbs-up',
                'color'   => 'green',
                'url'     => '/quotations',
                'is_read' => false,
                'created_at' => now()->subHours(3),
            ],
            [
                'type'    => 'quotation.created',
                'title'   => 'Quotation Created',
                'message' => 'Quotation QT-20240115-0013 created for Patel Corp',
                'icon'    => 'file-text',
                'color'   => 'accent',
                'url'     => '/quotations',
                'is_read' => true,
                'read_at' => now()->subHours(2),
                'created_at' => now()->subHours(9),
            ],
            [
                'type'    => 'quotation.rejected',
                'title'   => 'Quotation Rejected',
                'message' => 'Quotation QT-20240115-0010 rejected by Kumar Industries',
                'icon'    => 'thumbs-down',
                'color'   => 'red',
                'url'     => '/quotations',
                'is_read' => true,
                'read_at' => now()->subHours(3),
                'created_at' => now()->subHours(10),
            ],

            // ── Invoices ──────────────────────────────────────────
            [
                'type'    => 'invoice.paid',
                'title'   => 'Invoice Paid 💰',
                'message' => '💰 Invoice INV-20240115-0008 paid — ₹45,000 received',
                'icon'    => 'credit-card',
                'color'   => 'green',
                'url'     => '/invoices',
                'is_read' => false,
                'created_at' => now()->subHours(4),
            ],
            [
                'type'    => 'invoice.overdue',
                'title'   => 'Invoice Overdue',
                'message' => 'Invoice INV-20240110-0005 is overdue — ₹28,500 pending',
                'icon'    => 'alert-triangle',
                'color'   => 'red',
                'url'     => '/invoices',
                'is_read' => false,
                'created_at' => now()->subHours(5),
            ],
            [
                'type'    => 'invoice.created',
                'title'   => 'Invoice Generated',
                'message' => 'Invoice INV-20240115-0014 created for ₹67,200',
                'icon'    => 'file',
                'color'   => 'accent',
                'url'     => '/invoices',
                'is_read' => true,
                'read_at' => now()->subHours(4),
                'created_at' => now()->subHours(11),
            ],

            // ── System ────────────────────────────────────────────
            [
                'type'    => 'system.announcement',
                'title'   => 'Welcome to CrmPro! 🎉',
                'message' => 'Your CRM is set up and ready to use. Start by adding your first lead!',
                'icon'    => 'bell',
                'color'   => 'accent',
                'url'     => '/dashboard',
                'is_read' => true,
                'read_at' => now()->subDays(1),
                'created_at' => now()->subDays(1),
            ],
        ];

        foreach ($notifications as $n) {
            Notification::firstOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'user_id'   => $userId,
                    'type'      => $n['type'],
                    'message'   => $n['message'],
                ],
                array_merge($n, [
                    'tenant_id'    => $tenantId,
                    'user_id'      => $userId,
                    'channels_sent'=> ['in_app'],
                    'updated_at'   => $n['created_at'],
                ])
            );
        }
    }
}