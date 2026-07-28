<?php

return [

    // ── Lead ──────────────────────────────────────────────────────
    'lead' => [
        'statuses' => [
            'new'         => ['label' => 'New',         'color' => 'accent',  'bg' => 'accent-dim'],
            'contacted'   => ['label' => 'Contacted',   'color' => 'amber',   'bg' => 'amber-dim'],
            'qualified'   => ['label' => 'Qualified',   'color' => 'purple',  'bg' => 'purple-dim'],
            'proposal'    => ['label' => 'Proposal',    'color' => 'accent',  'bg' => 'accent-dim'],
            'negotiation' => ['label' => 'Negotiation', 'color' => 'amber',   'bg' => 'amber-dim'],
            'converted'   => ['label' => 'Converted',   'color' => 'green',   'bg' => 'green-dim'],
            'lost'        => ['label' => 'Lost',        'color' => 'red',     'bg' => 'red-dim'],
        ],
        'sources' => [
            'facebook'   => ['label' => 'Facebook',   'icon' => '📘'],
            'instagram'  => ['label' => 'Instagram',  'icon' => '📸'],
            'google'     => ['label' => 'Google Ads', 'icon' => '🔍'],
            'website'    => ['label' => 'Website',    'icon' => '🌐'],
            'whatsapp'   => ['label' => 'WhatsApp',   'icon' => '💬'],
            'referral'   => ['label' => 'Referral',   'icon' => '🤝'],
            'cold_call'  => ['label' => 'Cold Call',  'icon' => '📞'],
            'email'      => ['label' => 'Email',      'icon' => '✉️'],
            'walk_in'    => ['label' => 'Walk-in',    'icon' => '🚶'],
            'other'      => ['label' => 'Other',      'icon' => '📌'],
        ],
        'priorities' => [
            'low'    => ['label' => 'Low',    'color' => 'green'],
            'medium' => ['label' => 'Medium', 'color' => 'amber'],
            'high'   => ['label' => 'High',   'color' => 'red'],
        ],
    ],

    // ── Deal ──────────────────────────────────────────────────────
    'deal' => [
        'stages' => [
            'new'         => ['label' => 'New',         'color' => 'accent',  'bg' => 'accent-dim',  'probability' => 10],
            'proposal'    => ['label' => 'Proposal',    'color' => 'amber',   'bg' => 'amber-dim',   'probability' => 30],
            'negotiation' => ['label' => 'Negotiation', 'color' => 'purple',  'bg' => 'purple-dim',  'probability' => 60],
            'won'         => ['label' => 'Won',         'color' => 'green',   'bg' => 'green-dim',   'probability' => 100],
            'lost'        => ['label' => 'Lost',        'color' => 'red',     'bg' => 'red-dim',     'probability' => 0],
        ],
    ],

    // ── Followup ──────────────────────────────────────────────────
    'followup' => [
        'types' => [
            'call'     => ['label' => 'Call',      'color' => 'green',  'bg' => 'green-dim'],
            'email'    => ['label' => 'Email',     'color' => 'accent', 'bg' => 'accent-dim'],
            'whatsapp' => ['label' => 'WhatsApp',  'color' => 'green',  'bg' => 'green-dim'],
            'meeting'  => ['label' => 'Meeting',   'color' => 'purple', 'bg' => 'purple-dim'],
            'other'    => ['label' => 'Other',     'color' => 'amber',  'bg' => 'amber-dim'],
        ],
        'statuses' => [
            'scheduled'   => ['label' => 'Scheduled',   'color' => 'accent', 'bg' => 'accent-dim'],
            'done'        => ['label' => 'Done',        'color' => 'green',  'bg' => 'green-dim'],
            'missed'      => ['label' => 'Missed',      'color' => 'red',    'bg' => 'red-dim'],
            'rescheduled' => ['label' => 'Rescheduled', 'color' => 'amber',  'bg' => 'amber-dim'],
        ],
    ],

    // ── Quotation ─────────────────────────────────────────────────
    'quotation' => [
        'statuses' => [
            'draft'    => ['label' => 'Draft',    'color' => 'amber',  'bg' => 'amber-dim'],
            'sent'     => ['label' => 'Sent',     'color' => 'accent', 'bg' => 'accent-dim'],
            'accepted' => ['label' => 'Accepted', 'color' => 'green',  'bg' => 'green-dim'],
            'rejected' => ['label' => 'Rejected', 'color' => 'red',    'bg' => 'red-dim'],
        ],
        'default_tax'      => 18,
        'default_validity' => 30, // days
        'terms_default'    => "1. Payment due within 30 days of invoice date.\n2. Prices are inclusive of GST unless stated otherwise.\n3. This quotation is valid for the period mentioned above.",
    ],

    // ── Invoice ───────────────────────────────────────────────────
    'invoice' => [
        'statuses' => [
            'draft'   => ['label' => 'Draft',   'color' => 'amber',  'bg' => 'amber-dim'],
            'sent'    => ['label' => 'Sent',    'color' => 'accent', 'bg' => 'accent-dim'],
            'paid'    => ['label' => 'Paid',    'color' => 'green',  'bg' => 'green-dim'],
            'partial' => ['label' => 'Partial', 'color' => 'purple', 'bg' => 'purple-dim'],
            'overdue' => ['label' => 'Overdue', 'color' => 'red',    'bg' => 'red-dim'],
        ],
        'default_tax'     => 18,
        'default_due_days' => 30,
        'payment_methods' => [
            'cash'          => 'Cash',
            'card'          => 'Card',
            'bank_transfer' => 'Bank Transfer',
            'upi'           => 'UPI',
            'cheque'        => 'Cheque',
            'other'         => 'Other',
        ],
    ],

    // ── Task ──────────────────────────────────────────────────────
    'task' => [
        'statuses' => [
            'pending'     => ['label' => 'Pending',     'color' => 'amber',  'bg' => 'amber-dim'],
            'in_progress' => ['label' => 'In Progress', 'color' => 'accent', 'bg' => 'accent-dim'],
            'completed'   => ['label' => 'Completed',   'color' => 'green',  'bg' => 'green-dim'],
            'cancelled'   => ['label' => 'Cancelled',   'color' => 'red',    'bg' => 'red-dim'],
        ],
        'priorities' => [
            'low'    => ['label' => 'Low',    'color' => 'green'],
            'medium' => ['label' => 'Medium', 'color' => 'amber'],
            'high'   => ['label' => 'High',   'color' => 'red'],
        ],
    ],

    // ── Attendance ────────────────────────────────────────────────
    'attendance' => [
        'statuses' => [
            'present'  => ['label' => 'Present',   'color' => 'green',  'bg' => 'green-dim'],
            'absent'   => ['label' => 'Absent',    'color' => 'red',    'bg' => 'red-dim'],
            'half_day' => ['label' => 'Half Day',  'color' => 'amber',  'bg' => 'amber-dim'],
            'holiday'  => ['label' => 'Holiday',   'color' => 'purple', 'bg' => 'purple-dim'],
            'leave'    => ['label' => 'Leave',     'color' => 'accent', 'bg' => 'accent-dim'],
        ],
    ],

    // ── Currency ──────────────────────────────────────────────────
    'currency' => [
        'symbol'   => '₹',
        'code'     => 'INR',
        'position' => 'before', // before or after
    ],

    // ── Indian states ─────────────────────────────────────────────
    'states' => [
        'AP' => 'Andhra Pradesh',
        'AR' => 'Arunachal Pradesh',
        'AS' => 'Assam',
        'BR' => 'Bihar',
        'CG' => 'Chhattisgarh',
        'GA' => 'Goa',
        'GJ' => 'Gujarat',
        'HR' => 'Haryana',
        'HP' => 'Himachal Pradesh',
        'JH' => 'Jharkhand',
        'KA' => 'Karnataka',
        'KL' => 'Kerala',
        'MP' => 'Madhya Pradesh',
        'MH' => 'Maharashtra',
        'MN' => 'Manipur',
        'ML' => 'Meghalaya',
        'MZ' => 'Mizoram',
        'NL' => 'Nagaland',
        'OD' => 'Odisha',
        'PB' => 'Punjab',
        'RJ' => 'Rajasthan',
        'SK' => 'Sikkim',
        'TN' => 'Tamil Nadu',
        'TS' => 'Telangana',
        'TR' => 'Tripura',
        'UP' => 'Uttar Pradesh',
        'UK' => 'Uttarakhand',
        'WB' => 'West Bengal',
        'DL' => 'Delhi',
        'JK' => 'Jammu & Kashmir',
        'LA' => 'Ladakh',
        'PY' => 'Puducherry',
    ],

];