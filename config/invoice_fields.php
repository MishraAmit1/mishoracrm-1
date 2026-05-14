<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Invoice Sections
    |--------------------------------------------------------------------------
    */

    'sections' => [

        'basic' => [
            'title' => 'Invoice Information',
            'sub'   => 'Invoice number, dates aur status details',
            'icon'  => 'ti-file-invoice',
            'color' => 'blue',
        ],

        'customer' => [
            'title' => 'Customer Details',
            'sub'   => 'Client aur quotation related information',
            'icon'  => 'ti-user-circle',
            'color' => 'purple',
        ],

        'financial' => [
            'title' => 'Financial Details',
            'sub'   => 'Taxation, payment aur totals',
            'icon'  => 'ti-currency-rupee',
            'color' => 'green',
        ],

        'items' => [
            'title' => 'Invoice Items',
            'sub'   => 'Products/services line items',
            'icon'  => 'ti-list-details',
            'color' => 'amber',
        ],

        'payment' => [
            'title' => 'Payment Information',
            'sub'   => 'Razorpay aur payment tracking',
            'icon'  => 'ti-credit-card',
            'color' => 'teal',
        ],

        'other' => [
            'title' => 'Other Details',
            'sub'   => 'Additional notes & metadata',
            'icon'  => 'ti-notes',
            'color' => 'red',
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Invoice Fields
    |--------------------------------------------------------------------------
    */

    'fields' => [

        /*
        |--------------------------------------------------------------------------
        | BASIC
        |--------------------------------------------------------------------------
        */

        [
            'key'          => 'number',
            'label'        => 'Invoice Number',
            'type'         => 'text',
            'placeholder'  => 'INV-2026-0001',
            'required'     => true,
            'icon'         => 'ti-hash',
            'section'      => 'basic',
            'span'         => 'half',
            'show_in_list' => true,
            'copyable'     => true,
        ],

        [
            'key'          => 'date',
            'label'        => 'Invoice Date',
            'type'         => 'date',
            'required'     => true,
            'icon'         => 'ti-calendar',
            'section'      => 'basic',
            'span'         => 'half',
            'show_in_list' => true,
            'copyable'     => false,
        ],

        [
            'key'          => 'due_date',
            'label'        => 'Due Date',
            'type'         => 'date',
            'required'     => false,
            'icon'         => 'ti-calendar-time',
            'section'      => 'basic',
            'span'         => 'half',
            'show_in_list' => true,
            'copyable'     => false,
        ],

        [
            'key'          => 'status',
            'label'        => 'Invoice Status',
            'type'         => 'select',
            'required'     => true,
            'icon'         => 'ti-progress',
            'section'      => 'basic',
            'span'         => 'half',
            'show_in_list' => true,
            'copyable'     => false,

            'options' => [
                'draft'   => 'Draft',
                'sent'    => 'Sent',
                'paid'    => 'Paid',
                'partial' => 'Partial',
                'overdue' => 'Overdue',
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | CUSTOMER
        |--------------------------------------------------------------------------
        */

        [
            'key'          => 'contact_id',
            'label'        => 'Customer',
            'type'         => 'select',
            'placeholder'  => 'Select customer',
            'required'     => true,
            'icon'         => 'ti-user',
            'section'      => 'customer',
            'span'         => 'half',
            'show_in_list' => true,
            'copyable'     => false,

            'options' => [],
        ],

        [
            'key'          => 'quotation_id',
            'label'        => 'Quotation',
            'type'         => 'select',
            'placeholder'  => 'Linked quotation',
            'required'     => false,
            'icon'         => 'ti-file-description',
            'section'      => 'customer',
            'span'         => 'half',
            'show_in_list' => false,
            'copyable'     => false,

            'options' => [],
        ],

        /*
        |--------------------------------------------------------------------------
        | ITEMS
        |--------------------------------------------------------------------------
        */

        [
            'key'          => 'items',
            'label'        => 'Invoice Items',
            'type'         => 'repeater',
            'required'     => true,
            'icon'         => 'ti-list',
            'section'      => 'items',
            'span'         => 'full',
            'show_in_list' => false,
            'copyable'     => false,

            'fields' => [

                [
                    'key'         => 'name',
                    'label'       => 'Item Name',
                    'type'        => 'text',
                    'placeholder' => 'Product/service name',
                    'required'    => true,
                ],

                [
                    'key'         => 'description',
                    'label'       => 'Description',
                    'type'        => 'textarea',
                    'placeholder' => 'Short item description',
                    'required'    => false,
                ],

                [
                    'key'         => 'qty',
                    'label'       => 'Qty',
                    'type'        => 'number',
                    'required'    => true,
                    'default'     => 1,
                ],

                [
                    'key'         => 'price',
                    'label'       => 'Price',
                    'type'        => 'number',
                    'required'    => true,
                    'default'     => 0,
                ],

                [
                    'key'         => 'tax_percent',
                    'label'       => 'Tax %',
                    'type'        => 'number',
                    'required'    => false,
                    'default'     => 18,
                ],

                [
                    'key'         => 'total',
                    'label'       => 'Total',
                    'type'        => 'number',
                    'readonly'    => true,
                ],

            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | FINANCIAL
        |--------------------------------------------------------------------------
        */

        [
            'key'          => 'subtotal',
            'label'        => 'Subtotal',
            'type'         => 'number',
            'required'     => false,
            'readonly'     => true,
            'icon'         => 'ti-calculator',
            'section'      => 'financial',
            'span'         => 'half',
            'show_in_list' => true,
            'copyable'     => false,
        ],

        [
            'key'          => 'tax_percent',
            'label'        => 'Tax Percent',
            'type'         => 'number',
            'required'     => false,
            'default'      => 18,
            'icon'         => 'ti-percentage',
            'section'      => 'financial',
            'span'         => 'half',
            'show_in_list' => false,
            'copyable'     => false,
        ],

        [
            'key'          => 'tax_amount',
            'label'        => 'Tax Amount',
            'type'         => 'number',
            'readonly'     => true,
            'required'     => false,
            'icon'         => 'ti-receipt-tax',
            'section'      => 'financial',
            'span'         => 'half',
            'show_in_list' => true,
            'copyable'     => false,
        ],

        [
            'key'          => 'discount',
            'label'        => 'Discount',
            'type'         => 'number',
            'required'     => false,
            'default'      => 0,
            'icon'         => 'ti-discount',
            'section'      => 'financial',
            'span'         => 'half',
            'show_in_list' => false,
            'copyable'     => false,
        ],

        [
            'key'          => 'total',
            'label'        => 'Grand Total',
            'type'         => 'number',
            'readonly'     => true,
            'required'     => false,
            'icon'         => 'ti-currency-rupee',
            'section'      => 'financial',
            'span'         => 'half',
            'show_in_list' => true,
            'copyable'     => false,
        ],

        [
            'key'          => 'paid_amount',
            'label'        => 'Paid Amount',
            'type'         => 'number',
            'required'     => false,
            'default'      => 0,
            'icon'         => 'ti-wallet',
            'section'      => 'financial',
            'span'         => 'half',
            'show_in_list' => true,
            'copyable'     => false,
        ],

        /*
        |--------------------------------------------------------------------------
        | PAYMENT
        |--------------------------------------------------------------------------
        */

        [
            'key'          => 'razorpay_payment_id',
            'label'        => 'Razorpay Payment ID',
            'type'         => 'text',
            'placeholder'  => 'pay_xxxxxxxxx',
            'required'     => false,
            'icon'         => 'ti-brand-razorpay',
            'section'      => 'payment',
            'span'         => 'half',
            'show_in_list' => false,
            'copyable'     => true,
        ],

        [
            'key'          => 'paid_at',
            'label'        => 'Paid At',
            'type'         => 'datetime-local',
            'required'     => false,
            'icon'         => 'ti-clock-check',
            'section'      => 'payment',
            'span'         => 'half',
            'show_in_list' => false,
            'copyable'     => false,
        ],

        /*
        |--------------------------------------------------------------------------
        | OTHER
        |--------------------------------------------------------------------------
        */

        [
            'key'          => 'notes',
            'label'        => 'Notes',
            'type'         => 'textarea',
            'placeholder'  => 'Payment terms, bank details, remarks...',
            'required'     => false,
            'icon'         => 'ti-notes',
            'section'      => 'other',
            'span'         => 'full',
            'show_in_list' => false,
            'copyable'     => false,
        ],

    ],

];