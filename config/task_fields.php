<?php

return [

    // ── Stages (Kanban columns) ───────────────────────────────────
    'stages' => [
        'pending' => [
            'label'      => 'Pending',
            'color'      => '#378ADD',
            'bg'         => 'rgba(55,138,221,0.1)',
            'text_color' => '#185FA5',
        ],
        'in_progress' => [
            'label'      => 'In Progress',
            'color'      => '#EF9F27',
            'bg'         => 'rgba(239,159,39,0.1)',
            'text_color' => '#854F0B',
        ],
        'completed' => [
            'label'      => 'Completed',
            'color'      => '#1D9E75',
            'bg'         => 'rgba(29,158,117,0.1)',
            'text_color' => '#0F6E56',
        ],
        'cancelled' => [
            'label'      => 'Cancelled',
            'color'      => '#E05252',
            'bg'         => 'rgba(224,82,82,0.1)',
            'text_color' => '#A32D2D',
        ],
    ],

    // ── Priorities ────────────────────────────────────────────────
    'priorities' => [
        'low'    => ['label' => 'Low',    'color' => '#1D9E75', 'bg' => 'rgba(29,158,117,0.1)'],
        'medium' => ['label' => 'Medium', 'color' => '#EF9F27', 'bg' => 'rgba(239,159,39,0.1)'],
        'high'   => ['label' => 'High',   'color' => '#E05252', 'bg' => 'rgba(224,82,82,0.1)'],
    ],

    // ── List columns ──────────────────────────────────────────────
    'list_columns' => [
        'title'       => ['label' => 'Title',       'sortable' => true],
        'status'      => ['label' => 'Status',      'sortable' => true],
        'priority'    => ['label' => 'Priority',    'sortable' => true],
        'due_at'      => ['label' => 'Due Date',    'sortable' => true],
        'assigned_to' => ['label' => 'Assigned To', 'sortable' => false],
    ],

    'sections' => [

        'basic' => [
            'title' => 'Basic Information',
            'icon'  => 'ti-info-circle',
            'color' => 'primary',
        ],

        'relations' => [
            'title' => 'Relations',
            'icon'  => 'ti-link',
            'color' => 'secondary',
        ],

        'timeline' => [
            'title' => 'Timeline',
            'icon'  => 'ti-calendar',
            'color' => 'info',
        ],

        'assignment' => [
            'title' => 'Assignment',
            'icon'  => 'ti-user',
            'color' => 'success',
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Fields
    |--------------------------------------------------------------------------
    */
    'fields' => [

        [
            'key'         => 'title',
            'label'       => 'Task Title',
            'type'        => 'text',
            'required'    => true,
            'section'     => 'basic',
            'placeholder' => 'Enter task title',
            'span'        => 'full',
        ],

        [
            'key'         => 'description',
            'label'       => 'Description',
            'type'        => 'textarea',
            'required'    => false,
            'section'     => 'basic',
            'placeholder' => 'Task details...',
            'span'        => 'full',
        ],

        [
            'key'       => 'status',
            'label'     => 'Status',
            'type'      => 'select',
            'required'  => true,
            'section'   => 'basic',
            'options'   => [],
            'span'      => 'half',
        ],

        [
            'key'       => 'priority',
            'label'     => 'Priority',
            'type'      => 'select',
            'required'  => true,
            'section'   => 'basic',
            'options'   => [],
            'span'      => 'half',
        ],

        [
            'key'       => 'taskable_type',
            'label'     => 'Related Type',
            'type'      => 'select',
            'required'  => false,
            'section'   => 'relations',
            'options'   => [
                'lead'    => 'Lead',
                'contact' => 'Contact',
                'deal'    => 'Deal',
            ],
            'span'      => 'half',
        ],

        [
            'key'       => 'taskable_id',
            'label'     => 'Related Record',
            'type'      => 'select',
            'required'  => false,
            'section'   => 'relations',
            'options'   => [],
            'span'      => 'half',
        ],

        [
            'key'       => 'assigned_to',
            'label'     => 'Assigned To',
            'type'      => 'select',
            'required'  => false,
            'section'   => 'assignment',
            'options'   => [],
            'span'      => 'half',
        ],

        [
            'key'       => 'due_at',
            'label'     => 'Due Date',
            'type'      => 'date',
            'required'  => false,
            'section'   => 'timeline',
            'span'      => 'half',
        ],

        [
            'key'       => 'completed_at',
            'label'     => 'Completed At',
            'type'      => 'date',
            'required'  => false,
            'section'   => 'timeline',
            'span'      => 'half',
        ],
    ],


    
];
