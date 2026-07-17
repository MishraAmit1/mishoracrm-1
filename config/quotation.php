<?php

/**
 * ─────────────────────────────────────────────────────────────────

 *
 *  Ek jagah se sab views control hote hain.
 *  Statuses, tax options, terms templates, columns — sab yahan.
 * ─────────────────────────────────────────────────────────────────
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Statuses
    |--------------------------------------------------------------------------
    */
    'statuses' => [
        'draft' => [
            'label'      => 'Draft',
            'color'      => '#378ADD',
            'bg'         => '#E6F1FB',
            'text_color' => '#185FA5',
            'icon'       => 'ti-file',
        ],
        'sent' => [
            'label'      => 'Sent',
            'color'      => '#EF9F27',
            'bg'         => '#FAEEDA',
            'text_color' => '#854F0B',
            'icon'       => 'ti-send',
        ],
        'accepted' => [
            'label'      => 'Accepted',
            'color'      => '#1D9E75',
            'bg'         => '#E1F5EE',
            'text_color' => '#0F6E56',
            'icon'       => 'ti-circle-check',
        ],
        'rejected' => [
            'label'      => 'Rejected',
            'color'      => '#E24B4A',
            'bg'         => '#FCEBEB',
            'text_color' => '#A32D2D',
            'icon'       => 'ti-circle-x',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Tax Options
    |--------------------------------------------------------------------------
    */
    'tax_options' => [
        0  => 'No Tax (0%)',
        5  => 'GST 5%',
        12 => 'GST 12%',
        18 => 'GST 18%',
        28 => 'GST 28%',
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Terms & Conditions
    |--------------------------------------------------------------------------
    */
    'default_terms' => "1. This quotation is valid for the period mentioned above.\n2. Payment terms: 50% advance, 50% on delivery.\n3. Prices are inclusive of GST unless mentioned otherwise.\n4. Delivery timeline will be confirmed on order confirmation.\n5. Any changes to the scope of work may affect pricing.",

    /*
    |--------------------------------------------------------------------------
    | Default Notes
    |--------------------------------------------------------------------------
    */
    'default_notes' => "Thank you for the opportunity to provide this quotation. We look forward to working with you.",

    /*
    |--------------------------------------------------------------------------
    | Index List Columns
    |--------------------------------------------------------------------------
    */
    'list_columns' => [
        ['key' => 'number',      'label' => 'Quotation #',  'width' => '140px', 'sortable' => true],
        ['key' => 'contact',     'label' => 'Contact',      'width' => '180px', 'sortable' => false],
        ['key' => 'date',        'label' => 'Date',         'width' => '110px', 'sortable' => true],
        ['key' => 'valid_until', 'label' => 'Valid Until',  'width' => '110px', 'sortable' => false],
        ['key' => 'total',       'label' => 'Amount',       'width' => '120px', 'sortable' => true],
        ['key' => 'status',      'label' => 'Status',       'width' => '110px', 'sortable' => true],
    ],

    /*
    |--------------------------------------------------------------------------
    | Item Table Columns (create/edit line items)
    |--------------------------------------------------------------------------
    */
    'item_columns' => [
        ['key' => 'name',        'label' => 'Item / Service',  'width' => '24%'],
        ['key' => 'description', 'label' => 'Description',     'width' => '20%'],
        ['key' => 'quantity',    'label' => 'Qty',             'width' => '9%'],
        ['key' => 'rate',        'label' => 'Rate (₹)',        'width' => '13%'],
        ['key' => 'tax_percent', 'label' => 'GST %',           'width' => '10%'],
        ['key' => 'amount',      'label' => 'Amount (₹)',      'width' => '19%'],
        ['key' => 'actions',     'label' => '',                'width' => '5%'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Summary Cards for Index page
    |--------------------------------------------------------------------------
    */
    'summary_cards' => [
        ['key' => 'all',      'label' => 'All',       'icon' => 'ti-file-text',     'color' => '#378ADD', 'bg' => '#E6F1FB'],
        ['key' => 'draft',    'label' => 'Draft',     'icon' => 'ti-file',          'color' => '#378ADD', 'bg' => '#E6F1FB'],
        ['key' => 'sent',     'label' => 'Sent',      'icon' => 'ti-send',          'color' => '#EF9F27', 'bg' => '#FAEEDA'],
        ['key' => 'accepted', 'label' => 'Accepted',  'icon' => 'ti-circle-check',  'color' => '#1D9E75', 'bg' => '#E1F5EE'],
        ['key' => 'rejected', 'label' => 'Rejected',  'icon' => 'ti-circle-x',      'color' => '#E24B4A', 'bg' => '#FCEBEB'],
    ],

    /*
    |--------------------------------------------------------------------------
    | PDF Settings
    |--------------------------------------------------------------------------
    */
    'pdf' => [
        'paper'       => 'a4',
        'orientation' => 'portrait',
        'font'        => 'helvetica',
        'footer_text' => 'Thank you for your business.',
    ],

];