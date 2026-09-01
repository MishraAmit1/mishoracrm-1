<?php

/**
 * ─────────────────────────────────────────────────────────────────
 *  Contact Fields Config
 *  Location: config/contact_fields.php
 *
 *  Har field ka definition yahan hai.
 *  Create / Edit / Show — teeno pages is single file se load hote hain.
 *  Naya field add karna ho toh sirf yahan add karo — baaki sab automatic.
 * ─────────────────────────────────────────────────────────────────
 *
 *  Keys per field:
 *  ┌─────────────┬────────────────────────────────────────────────────────┐
 *  │ key         │ DB column / form input name (ContactRequest se match)  │
 *  │ label       │ Display label                                          │
 *  │ type        │ text | email | tel | number | textarea | select | date │
 *  │ placeholder │ Input placeholder                                      │
 *  │ required    │ bool — red * shown, HTML required added                │
 *  │ icon        │ Tabler icon name (ti-*)                                │
 *  │ section     │ Which section group this field belongs to              │
 *  │ span        │ 'full' = full width in grid, default = half            │
 *  │ hint        │ (optional) small hint text below input                 │
 *  │ options     │ (only for type=select) array of value=>label           │
 *  │ show_in_list│ bool — shown in show.blade info grid                   │
 *  │ copyable    │ bool — show copy button on show.blade                  │
 *  └─────────────┴────────────────────────────────────────────────────────┘
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Sections — defines order & metadata of section groups
    |--------------------------------------------------------------------------
    */
    'sections' => [
        'basic' => [
            'title' => 'Basic Information',
            'sub'   => 'Contact ka naam, phone aur primary details',
            'icon'  => 'ti-user',
            'color' => 'blue',   // blue | teal | amber | purple | red | green
        ],
        'company' => [
            'title' => 'Company Details',
            'sub'   => 'Organisation aur professional info',
            'icon'  => 'ti-building',
            'color' => 'purple',
        ],
        'address' => [
            'title' => 'Address',
            'sub'   => 'Location aur delivery details',
            'icon'  => 'ti-map-pin',
            'color' => 'teal',
        ],
        'other' => [
            'title' => 'Other Info',
            'sub'   => 'GST, linked lead aur additional notes',
            'icon'  => 'ti-notes',
            'color' => 'amber',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Fields
    |--------------------------------------------------------------------------
    */
    'fields' => [

        // ── Basic ────────────────────────────────────────────────────
        [
            'key'          => 'name',
            'label'        => 'Full Name',
            'type'         => 'text',
            'placeholder'  => 'e.g. Rahul Sharma',
            'required'     => true,
            'icon'         => 'ti-user',
            'section'      => 'basic',
            'span'         => 'half',
            'show_in_list' => true,
            'copyable'     => false,
        ],
        [
            'key'          => 'phone',
            'label'        => 'Phone',
            'type'         => 'tel',
            'placeholder'  => '+91 98765 43210',
            'required'     => true,
            'icon'         => 'ti-phone',
            'section'      => 'basic',
            'span'         => 'half',
            'hint'         => 'Used for SMS follow-ups',
            'show_in_list' => true,
            'copyable'     => true,
        ],
        [
            'key'          => 'email',
            'label'        => 'Email',
            'type'         => 'email',
            'placeholder'  => 'rahul@company.com',
            'required'     => false,
            'icon'         => 'ti-mail',
            'section'      => 'basic',
            'span'         => 'half',
            'show_in_list' => true,
            'copyable'     => true,
        ],

        // ── Company ──────────────────────────────────────────────────
        [
            'key'          => 'company',
            'label'        => 'Company',
            'type'         => 'text',
            'placeholder'  => 'Company name',
            'required'     => false,
            'icon'         => 'ti-building',
            'section'      => 'company',
            'span'         => 'half',
            'show_in_list' => true,
            'copyable'     => false,
        ],
        [
            'key'          => 'designation',
            'label'        => 'Designation',
            'type'         => 'text',
            'placeholder'  => 'e.g. Purchase Manager',
            'required'     => false,
            'icon'         => 'ti-briefcase',
            'section'      => 'company',
            'span'         => 'half',
            'show_in_list' => true,
            'copyable'     => false,
        ],
        [
            'key'          => 'gst_number',
            'label'        => 'GST Number',
            'type'         => 'text',
            'placeholder'  => '22AAAAA0000A1Z5',
            'required'     => false,
            'icon'         => 'ti-receipt-tax',
            'section'      => 'other',
            'span'         => 'half',
            'hint'         => '15-digit GST Identification Number',
            'show_in_list' => true,
            'copyable'     => true,
        ],

        // ── Address ──────────────────────────────────────────────────
        [
            'key'          => 'address',
            'label'        => 'Address',
            'type'         => 'textarea',
            'placeholder'  => 'Street address, area...',
            'required'     => false,
            'icon'         => 'ti-home',
            'section'      => 'address',
            'span'         => 'full',
            'show_in_list' => false,
            'copyable'     => false,
        ],
        [
            'key'          => 'city',
            'label'        => 'City',
            'type'         => 'text',
            'placeholder'  => 'Mumbai',
            'required'     => false,
            'icon'         => 'ti-building-community',
            'section'      => 'address',
            'span'         => 'half',
            'show_in_list' => true,
            'copyable'     => false,
        ],
        [
            'key'          => 'state',
            'label'        => 'State',
            'type'         => 'text',
            'placeholder'  => 'Maharashtra',
            'required'     => false,
            'icon'         => 'ti-map',
            'section'      => 'address',
            'span'         => 'half',
            'show_in_list' => false,
            'copyable'     => false,
        ],
        [
            'key'          => 'pincode',
            'label'        => 'Pincode',
            'type'         => 'text',
            'placeholder'  => '400001',
            'required'     => false,
            'icon'         => 'ti-mailbox',
            'section'      => 'address',
            'span'         => 'half',
            'show_in_list' => false,
            'copyable'     => false,
        ],

        // ── Other ────────────────────────────────────────────────────
        [
            'key'          => 'lead_id',
            'label'        => 'Linked Lead',
            'type'         => 'select',
            'placeholder'  => '— Select Lead —',
            'required'     => false,
            'icon'         => 'ti-target',
            'section'      => 'other',
            'span'         => 'half',
            'hint'         => 'Is contact ko kisi lead se link karo',
            'show_in_list' => true,
            'copyable'     => false,
            'options'      => [],   // Controller se $leads pass karo — yahan empty
        ],
        [
            'key'          => 'birthday',
            'label'        => 'Birthday',
            'type'         => 'date',
            'placeholder'  => '',
            'required'     => false,
            'icon'         => 'ti-cake',
            'section'      => 'other',
            'span'         => 'half',
            'hint'         => 'Used for birthday greetings & rewards',
            'show_in_list' => false,
            'copyable'     => false,
        ],
        [
            'key'          => 'anniversary',
            'label'        => 'Anniversary',
            'type'         => 'date',
            'placeholder'  => '',
            'required'     => false,
            'icon'         => 'ti-heart',
            'section'      => 'other',
            'span'         => 'half',
            'hint'         => 'Used for anniversary greetings & rewards',
            'show_in_list' => false,
            'copyable'     => false,
        ],
        [
            'key'          => 'referred_by_code',
            'label'        => 'Referred By (code)',
            'type'         => 'text',
            'placeholder'  => 'e.g. K7P2QF',
            'required'     => false,
            'icon'         => 'ti-users',
            'section'      => 'other',
            'span'         => 'half',
            'hint'         => "Enter the referring customer's loyalty referral code",
            'show_in_list' => false,
            'copyable'     => false,
            'module'       => 'loyalty',
        ],
        [
            'key'          => 'notes',
            'label'        => 'Notes',
            'type'         => 'textarea',
            'placeholder'  => 'Any additional notes, requirements or context...',
            'required'     => false,
            'icon'         => 'ti-notes',
            'section'      => 'other',
            'span'         => 'full',
            'show_in_list' => false,
            'copyable'     => false,
        ],
    ],

];