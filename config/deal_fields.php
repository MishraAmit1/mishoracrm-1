<?php

/**
 * ─────────────────────────────────────────────────────────────────
 *  Deal Fields Config
 *  Location : config/deal_fields.php
 *
 *  Ek hi jagah se teeno pages (create / edit / index) control hote hain.
 *  Naya field / stage add karna ho — sirf yahan change karo.
 * ─────────────────────────────────────────────────────────────────
 *
 *  Field keys:
 *  ┌──────────────┬──────────────────────────────────────────────────────┐
 *  │ key          │ DB column / form input name (DealRequest se match)   │
 *  │ label        │ Display label                                        │
 *  │ type         │ text|number|date|textarea|select|range               │
 *  │ placeholder  │ Input placeholder                                    │
 *  │ required     │ bool                                                 │
 *  │ icon         │ Tabler icon class (ti-*)                             │
 *  │ section      │ Form section group                                   │
 *  │ span         │ 'full' | 'half'                                      │
 *  │ hint         │ (optional) hint text                                 │
 *  │ show_in_list │ bool — shown in list/kanban card                     │
 *  │ show_in_card │ bool — shown on kanban card                          │
 *  │ sortable     │ bool — sortable column in list view                  │
 *  │ prefix       │ (optional) e.g. '₹'                                  │
 *  │ suffix       │ (optional) e.g. '%'                                  │
 *  └──────────────┴──────────────────────────────────────────────────────┘
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Stages — pipeline order, colors, default probabilities
    |--------------------------------------------------------------------------
    */
    'stages' => [
        'new' => [
            'label'       => 'New',
            'color'       => '#378ADD',
            'bg'          => '#E6F1FB',
            'text_color'  => '#185FA5',
            'probability' => 10,
            'icon'        => 'ti-sparkles',
        ],
        'proposal' => [
            'label'       => 'Proposal',
            'color'       => '#EF9F27',
            'bg'          => '#FAEEDA',
            'text_color'  => '#854F0B',
            'probability' => 30,
            'icon'        => 'ti-file-description',
        ],
        'negotiation' => [
            'label'       => 'Negotiation',
            'color'       => '#534AB7',
            'bg'          => '#EEEDFE',
            'text_color'  => '#3C3489',
            'probability' => 60,
            'icon'        => 'ti-messages',
        ],
        'won' => [
            'label'       => 'Won',
            'color'       => '#1D9E75',
            'bg'          => '#E1F5EE',
            'text_color'  => '#0F6E56',
            'probability' => 100,
            'icon'        => 'ti-trophy',
        ],
        'lost' => [
            'label'       => 'Lost',
            'color'       => '#E24B4A',
            'bg'          => '#FCEBEB',
            'text_color'  => '#A32D2D',
            'probability' => 0,
            'icon'        => 'ti-x',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Sections — form grouping (create / edit pages)
    |--------------------------------------------------------------------------
    */
    'sections' => [
        'basic' => [
            'title' => 'Deal Information',
            'sub'   => 'Title, value aur pipeline stage',
            'icon'  => 'ti-currency-rupee',
            'color' => 'blue',
        ],
        'relations' => [
            'title' => 'Links',
            'sub'   => 'Contact aur lead se connect karo',
            'icon'  => 'ti-link',
            'color' => 'purple',
        ],
        'timeline' => [
            'title' => 'Timeline',
            'sub'   => 'Expected close aur probability',
            'icon'  => 'ti-calendar',
            'color' => 'teal',
        ],
        'assignment' => [
            'title' => 'Assignment',
            'sub'   => 'Deal kis team member ko assign karein',
            'icon'  => 'ti-user-check',
            'color' => 'amber',
        ],
        'notes' => [
            'title' => 'Notes & Reason',
            'sub'   => 'Additional notes ya lost reason',
            'icon'  => 'ti-notes',
            'color' => 'green',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Fields
    |--------------------------------------------------------------------------
    */
    'fields' => [

        // ── Basic ────────────────────────────────────────────────
        [
            'key'          => 'title',
            'label'        => 'Deal Title',
            'type'         => 'text',
            'placeholder'  => 'e.g. Tata Motors Supply Contract',
            'required'     => true,
            'icon'         => 'ti-tag',
            'section'      => 'basic',
            'span'         => 'full',
            'show_in_list' => true,
            'show_in_card' => true,
            'sortable'     => true,
        ],
        [
            'key'          => 'value',
            'label'        => 'Deal Value',
            'type'         => 'number',
            'placeholder'  => '0',
            'required'     => true,
            'icon'         => 'ti-currency-rupee',
            'section'      => 'basic',
            'span'         => 'half',
            'prefix'       => '₹',
            'hint'         => 'Expected deal value',
            'show_in_list' => true,
            'show_in_card' => true,
            'sortable'     => true,
        ],
        [
            'key'          => 'stage',
            'label'        => 'Stage',
            'type'         => 'select',
            'placeholder'  => '— Select Stage —',
            'required'     => true,
            'icon'         => 'ti-git-branch',
            'section'      => 'basic',
            'span'         => 'half',
            'options'      => [],    // populated from stages config
            'show_in_list' => true,
            'show_in_card' => false,
            'sortable'     => true,
        ],

        // ── Relations ────────────────────────────────────────────
        [
            'key'          => 'contact_id',
            'label'        => 'Contact',
            'type'         => 'select',
            'placeholder'  => '— Select Contact —',
            'required'     => false,
            'icon'         => 'ti-user',
            'section'      => 'relations',
            'span'         => 'half',
            'options'      => [],   // from controller $contacts
            'show_in_list' => true,
            'show_in_card' => true,
            'sortable'     => false,
        ],
        [
            'key'          => 'lead_id',
            'label'        => 'Linked Lead',
            'type'         => 'select',
            'placeholder'  => '— Select Lead —',
            'required'     => false,
            'icon'         => 'ti-target',
            'section'      => 'relations',
            'span'         => 'half',
            'options'      => [],   // from controller $leads
            'hint'         => 'Is deal ko kisi lead se link karo',
            'show_in_list' => false,
            'show_in_card' => false,
            'sortable'     => false,
        ],

        // ── Timeline ─────────────────────────────────────────────
        [
            'key'          => 'expected_close_date',
            'label'        => 'Expected Close Date',
            'type'         => 'date',
            'placeholder'  => '',
            'required'     => false,
            'icon'         => 'ti-calendar',
            'section'      => 'timeline',
            'span'         => 'half',
            'show_in_list' => true,
            'show_in_card' => true,
            'sortable'     => true,
        ],
        [
            'key'          => 'probability',
            'label'        => 'Probability (%)',
            'type'         => 'range',
            'placeholder'  => '',
            'required'     => false,
            'icon'         => 'ti-chart-bar',
            'section'      => 'timeline',
            'span'         => 'half',
            'suffix'       => '%',
            'hint'         => 'Auto-set hoga stage ke hisab se',
            'show_in_list' => true,
            'show_in_card' => true,
            'sortable'     => false,
        ],
        [
            'key'          => 'actual_close_date',
            'label'        => 'Actual Close Date',
            'type'         => 'date',
            'placeholder'  => '',
            'required'     => false,
            'icon'         => 'ti-calendar-check',
            'section'      => 'timeline',
            'span'         => 'half',
            'hint'         => 'Won hone par automatically set hota hai',
            'show_in_list' => false,
            'show_in_card' => false,
            'sortable'     => false,
        ],

        // ── Assignment ───────────────────────────────────────────
        [
            'key'          => 'assigned_to',
            'label'        => 'Assigned To',
            'type'         => 'select',
            'placeholder'  => '— Unassigned —',
            'required'     => false,
            'icon'         => 'ti-user-check',
            'section'      => 'assignment',
            'span'         => 'full',
            'options'      => [],   // from controller $staffList
            'show_in_list' => true,
            'show_in_card' => true,
            'sortable'     => false,
        ],

        // ── Notes ────────────────────────────────────────────────
        [
            'key'          => 'notes',
            'label'        => 'Notes',
            'type'         => 'textarea',
            'placeholder'  => 'Any relevant notes, requirements or context...',
            'required'     => false,
            'icon'         => 'ti-notes',
            'section'      => 'notes',
            'span'         => 'full',
            'show_in_list' => false,
            'show_in_card' => false,
            'sortable'     => false,
        ],
        [
            'key'          => 'lost_reason',
            'label'        => 'Lost Reason',
            'type'         => 'textarea',
            'placeholder'  => 'Why was this deal lost?',
            'required'     => false,
            'icon'         => 'ti-alert-circle',
            'section'      => 'notes',
            'span'         => 'full',
            'hint'         => 'Sirf "Lost" stage select karne par relevant',
            'show_in_list' => false,
            'show_in_card' => false,
            'sortable'     => false,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | List View Columns — order + width
    | Only fields with show_in_list = true are shown
    |--------------------------------------------------------------------------
    */
    'list_columns' => [
        ['key' => 'title',               'label' => 'Deal',        'width' => '220px'],
        ['key' => 'contact_id',          'label' => 'Contact',     'width' => '130px'],
        ['key' => 'stage',               'label' => 'Stage',       'width' => '115px'],
        ['key' => 'value',               'label' => 'Value',       'width' => '120px'],
        ['key' => 'probability',         'label' => 'Prob%',       'width' => '100px'],
        ['key' => 'expected_close_date', 'label' => 'Close Date',  'width' => '115px'],
        ['key' => 'assigned_to',         'label' => 'Assigned',    'width' => '120px'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Kanban Card Fields — shown on each deal card
    |--------------------------------------------------------------------------
    */
    'kanban_card_fields' => [
        'primary'     => 'title',
        'value'       => 'value',
        'contact'     => 'contact_id',
        'probability' => 'probability',
        'close_date'  => 'expected_close_date',
        'assignee'    => 'assigned_to',
    ],

];