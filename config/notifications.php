<?php

return [

    // ── Notification Types ────────────────────────────────────────
    // Future mein WhatsApp/Email/Slack add karne ke liye
    // bas yahan channel add karo — code automatically use karega

    'types' => [

        // ── Leads ─────────────────────────────────────────────────
        'lead.created' => [
            'label'    => 'New Lead Added',
            'icon'     => 'user-plus',
            'color'    => 'accent',
            'group'    => 'Leads',
            'channels' => ['in_app'],           // default channels
            'message'  => 'New lead {{name}} added from {{source}}',
        ],
        'lead.assigned' => [
            'label'    => 'Lead Assigned',
            'icon'     => 'user-check',
            'color'    => 'accent',
            'group'    => 'Leads',
            'channels' => ['in_app'],
            'message'  => 'Lead {{name}} assigned to you',
        ],
        'lead.status_changed' => [
            'label'    => 'Lead Status Changed',
            'icon'     => 'refresh',
            'color'    => 'amber',
            'group'    => 'Leads',
            'channels' => ['in_app'],
            'message'  => 'Lead {{name}} moved to {{status}}',
        ],
        'lead.converted' => [
            'label'    => 'Lead Converted',
            'icon'     => 'check-circle',
            'color'    => 'green',
            'group'    => 'Leads',
            'channels' => ['in_app'],
            'message'  => 'Lead {{name}} converted to contact',
        ],
        'lead.sla_breached' => [
            'label'    => 'Lead SLA Breached',
            'icon'     => 'alert-circle',
            'color'    => 'red',
            'group'    => 'Leads',
            'channels' => ['in_app'],
            'message'  => 'Lead {{name}} has had no contact since it was created — follow up now',
        ],

        // ── Deals ─────────────────────────────────────────────────
        'deal.assigned' => [
            'label'    => 'Deal Assigned',
            'icon'     => 'user-check',
            'color'    => 'accent',
            'group'    => 'Deals',
            'channels' => ['in_app'],
            'message'  => 'Deal "{{title}}" assigned to you',
        ],
        'deal.created' => [
            'label'    => 'New Deal Created',
            'icon'     => 'briefcase',
            'color'    => 'accent',
            'group'    => 'Deals',
            'channels' => ['in_app'],
            'message'  => 'New deal "{{title}}" created worth ₹{{value}}',
        ],
        'deal.stage_changed' => [
            'label'    => 'Deal Stage Changed',
            'icon'     => 'arrow-right',
            'color'    => 'amber',
            'group'    => 'Deals',
            'channels' => ['in_app'],
            'message'  => 'Deal "{{title}}" moved to {{stage}}',
        ],
        'deal.won' => [
            'label'    => 'Deal Won 🎉',
            'icon'     => 'trophy',
            'color'    => 'green',
            'group'    => 'Deals',
            'channels' => ['in_app', 'email'],
            'message'  => '🎉 Deal "{{title}}" marked as Won! ₹{{value}}',
        ],
        'deal.lost' => [
            'label'    => 'Deal Lost',
            'icon'     => 'x-circle',
            'color'    => 'red',
            'group'    => 'Deals',
            'channels' => ['in_app'],
            'message'  => 'Deal "{{title}}" marked as Lost',
        ],

        // ── Follow-ups ────────────────────────────────────────────
        'followup.due' => [
            'label'    => 'Follow-up Due',
            'icon'     => 'clock',
            'color'    => 'amber',
            'group'    => 'Follow-ups',
            'channels' => ['in_app', 'whatsapp', 'push'],
            'message'  => 'Follow-up with {{name}} is due now',
        ],
        'followup.overdue' => [
            'label'    => 'Follow-up Overdue',
            'icon'     => 'alert-circle',
            'color'    => 'red',
            'group'    => 'Follow-ups',
            'channels' => ['in_app', 'whatsapp', 'push'],
            'message'  => 'Follow-up with {{name}} is overdue!',
        ],
        'followup.scheduled' => [
            'label'    => 'Follow-up Scheduled',
            'icon'     => 'calendar',
            'color'    => 'accent',
            'group'    => 'Follow-ups',
            'channels' => ['in_app'],
            'message'  => 'Follow-up with {{name}} scheduled for {{date}}',
        ],

        // ── Tasks ─────────────────────────────────────────────────
        'task.assigned' => [
            'label'    => 'Task Assigned',
            'icon'     => 'check-square',
            'color'    => 'accent',
            'group'    => 'Tasks',
            'channels' => ['in_app'],
            'message'  => 'Task "{{title}}" assigned to you',
        ],
        'task.due' => [
            'label'    => 'Task Due',
            'icon'     => 'clock',
            'color'    => 'amber',
            'group'    => 'Tasks',
            'channels' => ['in_app'],
            'message'  => 'Task "{{title}}" is due today',
        ],
        'task.completed' => [
            'label'    => 'Task Completed',
            'icon'     => 'check',
            'color'    => 'green',
            'group'    => 'Tasks',
            'channels' => ['in_app'],
            'message'  => 'Task "{{title}}" marked as completed',
        ],

        // ── Quotations ────────────────────────────────────────────
        'quotation.created' => [
            'label'    => 'Quotation Created',
            'icon'     => 'file-text',
            'color'    => 'accent',
            'group'    => 'Quotations',
            'channels' => ['in_app'],
            'message'  => 'Quotation {{number}} created for {{contact}}',
        ],
        'quotation.accepted' => [
            'label'    => 'Quotation Accepted',
            'icon'     => 'thumbs-up',
            'color'    => 'green',
            'group'    => 'Quotations',
            'channels' => ['in_app', 'email'],
            'message'  => '✅ Quotation {{number}} accepted by {{contact}}',
        ],
        'quotation.rejected' => [
            'label'    => 'Quotation Rejected',
            'icon'     => 'thumbs-down',
            'color'    => 'red',
            'group'    => 'Quotations',
            'channels' => ['in_app'],
            'message'  => 'Quotation {{number}} rejected by {{contact}}',
        ],

        // ── Invoices ──────────────────────────────────────────────
        'invoice.created' => [
            'label'    => 'Invoice Created',
            'icon'     => 'file',
            'color'    => 'accent',
            'group'    => 'Invoices',
            'channels' => ['in_app'],
            'message'  => 'Invoice {{number}} created for ₹{{amount}}',
        ],
        'invoice.paid' => [
            'label'    => 'Invoice Paid',
            'icon'     => 'credit-card',
            'color'    => 'green',
            'group'    => 'Invoices',
            'channels' => ['in_app', 'email'],
            'message'  => '💰 Invoice {{number}} paid — ₹{{amount}} received',
        ],
        'invoice.payment_due' => [
            'label'    => 'Payment Due Soon',
            'icon'     => 'clock',
            'color'    => 'amber',
            'group'    => 'Invoices',
            'channels' => ['in_app', 'email'],
            'message'  => 'Invoice {{number}} — ₹{{amount}} due on {{date}}',
        ],
        'invoice.overdue' => [
            'label'    => 'Invoice Overdue',
            'icon'     => 'alert-triangle',
            'color'    => 'red',
            'group'    => 'Invoices',
            'channels' => ['in_app', 'email'],
            'message'  => 'Invoice {{number}} is overdue — ₹{{amount}} pending',
        ],

        // ── Products ──────────────────────────────────────────────
        'product.low_stock' => [
            'label'    => 'Low Stock Alert',
            'icon'     => 'alert-triangle',
            'color'    => 'amber',
            'group'    => 'Products',
            'channels' => ['in_app'],
            'message'  => '{{name}} is low on stock ({{stock}} remaining)',
        ],
        'product.batch_expiring' => [
            'label'    => 'Batch Expiring Soon',
            'icon'     => 'alert-triangle',
            'color'    => 'amber',
            'group'    => 'Products',
            'channels' => ['in_app'],
            'message'  => 'Batch {{batch_number}} of {{name}} ({{quantity}} left) expires on {{expiry_date}}',
        ],

        // ── Services ──────────────────────────────────────────────
        'subscription.expiring' => [
            'label'    => 'Subscription Expiring Soon',
            'icon'     => 'clock',
            'color'    => 'amber',
            'group'    => 'Services',
            'channels' => ['in_app', 'email'],
            'message'  => '{{contact_name}}\'s {{service_name}} subscription expires on {{expiry_date}}',
        ],

        // ── Tickets ───────────────────────────────────────────────
        'ticket.created' => [
            'label'    => 'New Support Ticket',
            'icon'     => 'life-buoy',
            'color'    => 'accent',
            'group'    => 'Tickets',
            'channels' => ['in_app', 'email'],
            'message'  => 'New ticket from {{contact_name}}: {{subject}}',
        ],
        'ticket.assigned' => [
            'label'    => 'Ticket Assigned to You',
            'icon'     => 'life-buoy',
            'color'    => 'accent',
            'group'    => 'Tickets',
            'channels' => ['in_app', 'email'],
            'message'  => 'You were assigned ticket: {{subject}}',
        ],
        'ticket.customer_replied' => [
            'label'    => 'Customer Replied to Ticket',
            'icon'     => 'life-buoy',
            'color'    => 'amber',
            'group'    => 'Tickets',
            'channels' => ['in_app'],
            'message'  => 'New reply on ticket: {{subject}}',
        ],

        // ── Purchase ──────────────────────────────────────────────
        'purchase_request.submitted' => [
            'label'    => 'New Purchase Request',
            'icon'     => 'file-text',
            'color'    => 'accent',
            'group'    => 'Purchase',
            'channels' => ['in_app'],
            'message'  => 'Purchase request {{number}} from {{requester}} needs your approval',
        ],
        'purchase_request.approved' => [
            'label'    => 'Purchase Request Approved',
            'icon'     => 'check-circle',
            'color'    => 'green',
            'group'    => 'Purchase',
            'channels' => ['in_app'],
            'message'  => 'Your purchase request {{number}} was approved',
        ],
        'purchase_request.rejected' => [
            'label'    => 'Purchase Request Rejected',
            'icon'     => 'x-circle',
            'color'    => 'red',
            'group'    => 'Purchase',
            'channels' => ['in_app'],
            'message'  => 'Your purchase request {{number}} was rejected',
        ],

        // ── System ────────────────────────────────────────────────
        'system.announcement' => [
            'label'    => 'Announcement',
            'icon'     => 'bell',
            'color'    => 'accent',
            'group'    => 'System',
            'channels' => ['in_app'],
            'message'  => '{{message}}',
        ],
    ],

    // ── Available channels ────────────────────────────────────────
    // Future channels add karne ke liye yahan add karo
    'channels' => [
        'in_app'   => ['label' => 'In-App Bell',  'icon' => 'bell',    'enabled' => true],
        'email'    => ['label' => 'Email',         'icon' => 'mail',    'enabled' => true],
        'whatsapp' => ['label' => 'WhatsApp',      'icon' => 'message', 'enabled' => true],
        'slack'    => ['label' => 'Slack',         'icon' => 'slack',   'enabled' => true],
        'push'     => ['label' => 'Push',          'icon' => 'bell',    'enabled' => true],
    ],

    // ── Icon map (Heroicons names) ────────────────────────────────
    'icons' => [
        'bell'           => '<path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"/>',
        'user-plus'      => '<path stroke-linecap="round" stroke-linejoin="round" d="M19 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zM4 19.235v-.11a6.375 6.375 0 0112.75 0v.109A12.318 12.318 0 0110.374 21c-2.331 0-4.512-.645-6.374-1.766z"/>',
        'user-check'     => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>',
        'check-circle'   => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>',
        'briefcase'      => '<path stroke-linecap="round" stroke-linejoin="round" d="M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 00.75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 00-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0112 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 01-.673-.38m0 0A2.18 2.18 0 013 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 013.413-.387m7.5 0V5.25A2.25 2.25 0 0013.5 3h-3a2.25 2.25 0 00-2.25 2.25v.894m7.5 0a48.667 48.667 0 00-7.5 0"/>',
        'trophy'         => '<path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 013 3h-15a3 3 0 013-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.007 0H9.497m5.007 0a7.454 7.454 0 01-.982-3.172M9.497 14.25a7.454 7.454 0 00.981-3.172M5.25 4.236c-.982.143-1.954.317-2.916.52A6.003 6.003 0 007.73 9.728M5.25 4.236V4.5c0 2.108.966 3.99 2.48 5.228M5.25 4.236V2.721C7.456 2.41 9.71 2.25 12 2.25c2.291 0 4.545.16 6.75.47v1.516M7.73 9.728a6.726 6.726 0 002.748 1.35m8.272-6.842V4.5c0 2.108-.966 3.99-2.48 5.228m2.48-5.492a46.32 46.32 0 012.916.52 6.003 6.003 0 01-5.395 4.972m0 0a6.726 6.726 0 01-2.749 1.35m0 0a6.772 6.772 0 01-3.044 0"/>',
        'clock'          => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>',
        'alert-circle'   => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>',
        'calendar'       => '<path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/>',
        'check-square'   => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>',
        'check'          => '<path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>',
        'file-text'      => '<path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>',
        'thumbs-up'      => '<path stroke-linecap="round" stroke-linejoin="round" d="M6.633 10.5c.806 0 1.533-.446 2.031-1.08a9.041 9.041 0 012.861-2.4c.723-.384 1.35-.956 1.653-1.715a4.498 4.498 0 00.322-1.672V3a.75.75 0 01.75-.75A2.25 2.25 0 0116.5 4.5c0 1.152-.26 2.243-.723 3.218-.266.558.107 1.282.725 1.282h3.126c1.026 0 1.945.694 2.054 1.715.045.422.068.85.068 1.285a11.95 11.95 0 01-2.649 7.521c-.388.482-.987.729-1.605.729H13.48c-.483 0-.964-.078-1.423-.23l-3.114-1.04a4.501 4.501 0 00-1.423-.23H5.904M14.25 9h2.25M5.904 18.75c.083.205.173.405.27.602.197.4-.078.898-.523.898h-.908c-.889 0-1.713-.518-1.972-1.368a12 12 0 01-.521-3.507c0-1.553.295-3.036.831-4.398C3.387 10.203 4.167 9.75 5 9.75h1.053c.472 0 .745.556.5.96a8.958 8.958 0 00-1.302 4.665c0 1.194.232 2.333.654 3.375z"/>',
        'thumbs-down'    => '<path stroke-linecap="round" stroke-linejoin="round" d="M7.5 15h2.25m8.024-9.75c.011.05.028.1.052.148.591 1.2.924 2.55.924 3.977a8.96 8.96 0 01-.999 4.125m.023-8.25c-.076-.365.183-.75.575-.75h.908c.889 0 1.713.518 1.972 1.368.339 1.11.521 2.287.521 3.507 0 1.553-.295 3.036-.831 4.398C20.613 14.547 19.833 15 19 15h-1.053c-.472 0-.745-.556-.5-.96a8.95 8.95 0 00.303-.54m.023-8.25H16.48a4.5 4.5 0 01-1.423-.23l-3.114-1.04a4.5 4.5 0 00-1.423-.23H6.504c-.618 0-1.217.247-1.605.729A11.95 11.95 0 002.25 12c0 .434.023.863.068 1.285C2.427 14.306 3.346 15 4.372 15h3.126c.618 0 .991.724.725 1.282A7.471 7.471 0 007.5 19.5a2.25 2.25 0 002.25 2.25.75.75 0 00.75-.75v-.633c0-.573.11-1.14.322-1.672.304-.76.93-1.33 1.653-1.715a9.04 9.04 0 002.86-2.4c.498-.634 1.226-1.08 2.032-1.08h.384"/>',
        'file'           => '<path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>',
        'credit-card'    => '<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 21z"/>',
        'alert-triangle' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>',
        'refresh'        => '<path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99"/>',
        'arrow-right'    => '<path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/>',
        'x-circle'       => '<path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>',
    ],

];