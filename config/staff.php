<?php

return [

    'employment_types' => [
        'full_time' => [
            'label' => 'Full Time',
            'color' => 'accent',
            'bg'    => 'accent-dim',
        ],
        'part_time' => [
            'label' => 'Part Time',
            'color' => 'amber',
            'bg'    => 'amber-dim',
        ],
        'contract' => [
            'label' => 'Contract',
            'color' => 'purple',
            'bg'    => 'purple-dim',
        ],
        'intern' => [
            'label' => 'Intern',
            'color' => 'green',
            'bg'    => 'green-dim',
        ],
    ],

    'roles' => [
        'tenant_admin' => [
            'label' => 'Admin',
            'desc'  => 'Full access to all modules',
            'color' => 'red',
            'bg'    => 'red-dim',
        ],
        'manager' => [
            'label' => 'Manager',
            'desc'  => 'Manage leads, deals & staff',
            'color' => 'accent',
            'bg'    => 'accent-dim',
        ],
        'staff' => [
            'label' => 'Staff',
            'desc'  => 'Basic CRM access only',
            'color' => 'green',
            'bg'    => 'green-dim',
        ],
    ],

    'avatar_colors' => [
        ['bg' => '#E6F1FB', 'text' => '#185FA5'],
        ['bg' => '#E1F5EE', 'text' => '#0F6E56'],
        ['bg' => '#FAEEDA', 'text' => '#854F0B'],
        ['bg' => '#EEEDFE', 'text' => '#3C3489'],
        ['bg' => '#FEE2E2', 'text' => '#991B1B'],
        ['bg' => '#FEF3C7', 'text' => '#92400E'],
    ],

    'form_fields' => [

        'account' => [
            'title'  => 'Account Information',
            'sub'    => 'Login credentials for this staff member',
            'fields' => [
                [
                    'name'        => 'name',
                    'label'       => 'Full Name',
                    'type'        => 'text',
                    'placeholder' => 'Rahul Sharma',
                    'required'    => true,
                    'span'        => 1,
                ],
                [
                    'name'        => 'email',
                    'label'       => 'Email',
                    'type'        => 'email',
                    'placeholder' => 'rahul@company.com',
                    'required'    => true,
                    'span'        => 1,
                ],
                [
                    'name'        => 'password',
                    'label'       => 'Password',
                    'type'        => 'password',
                    'placeholder' => 'Min. 8 characters',
                    'required'    => true,
                    'span'        => 1,
                ],
                [
                    'name'        => 'password_confirmation',
                    'label'       => 'Confirm Password',
                    'type'        => 'password',
                    'placeholder' => 'Repeat password',
                    'required'    => true,
                    'span'        => 1,
                ],
                [
                    'name'        => 'phone',
                    'label'       => 'Phone',
                    'type'        => 'tel',
                    'placeholder' => '+91 98765 43210',
                    'required'    => false,
                    'span'        => 1,
                ],
            ],
        ],

        'job' => [
            'title'  => 'Job Details',
            'sub'    => 'Department, designation and employment info',
            'fields' => [
                [
                    'name'        => 'designation',
                    'label'       => 'Designation',
                    'type'        => 'text',
                    'placeholder' => 'e.g. Sales Executive',
                    'required'    => false,
                    'span'        => 1,
                ],
                [
                    'name'        => 'employee_code',
                    'label'       => 'Employee Code',
                    'type'        => 'text',
                    'placeholder' => 'EMP-001',
                    'required'    => false,
                    'span'        => 1,
                ],
                [
                    'name'        => 'joining_date',
                    'label'       => 'Joining Date',
                    'type'        => 'date',
                    'placeholder' => '',
                    'required'    => false,
                    'span'        => 1,
                ],
                [
                    'name'        => 'salary',
                    'label'       => 'Salary (₹)',
                    'type'        => 'number',
                    'placeholder' => '0',
                    'required'    => false,
                    'hint'        => 'Monthly CTC',
                    'span'        => 1,
                ],
            ],
        ],

    ],

];