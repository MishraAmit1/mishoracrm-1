<?php

namespace Database\Seeders;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Database\Seeder;

class LeadSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = User::where('user_type', 'tenant_admin')->first();
        if (!$tenant) {
            $this->command->warn('No tenant_admin found. Run SuperAdminSeeder first.');
            return;
        }

        $tenantId  = $tenant->tenant_id;
        $createdBy = $tenant->id;

        $staff = User::where('tenant_id', $tenantId)
            ->where('user_type', '!=', 'superadmin')
            ->pluck('id')
            ->toArray();

        $assignees = count($staff) ? $staff : [$createdBy];

        $leads = [
            // New
            ['name' => 'Arjun Mehta',      'phone' => '9876543210', 'email' => 'arjun.mehta@gmail.com',   'company' => 'Mehta Traders',       'designation' => 'Owner',         'city' => 'Mumbai',    'state' => 'Maharashtra', 'source' => 'website',   'status' => 'new',       'priority' => 'high',   'lead_value' => 450000],
            ['name' => 'Priya Sharma',      'phone' => '9988776655', 'email' => 'priya.sharma@yahoo.com',  'company' => 'Sharma Enterprises',  'designation' => 'Manager',       'city' => 'Delhi',     'state' => 'Delhi',       'source' => 'referral',  'status' => 'new',       'priority' => 'medium', 'lead_value' => 180000],
            ['name' => 'Rohit Patel',       'phone' => '9012345678', 'email' => 'rohit.patel@outlook.com', 'company' => 'Patel Industries',    'designation' => 'Director',      'city' => 'Ahmedabad', 'state' => 'Gujarat',     'source' => 'google',    'status' => 'new',       'priority' => 'low',    'lead_value' => 95000],
            ['name' => 'Sneha Verma',       'phone' => '9123456789', 'email' => 'sneha.v@gmail.com',       'company' => null,                  'designation' => null,            'city' => 'Pune',      'state' => 'Maharashtra', 'source' => 'instagram', 'status' => 'new',       'priority' => 'medium', 'lead_value' => 60000],
            ['name' => 'Kiran Joshi',       'phone' => '9345678901', 'email' => null,                      'company' => 'Joshi & Sons',        'designation' => 'Partner',       'city' => 'Nashik',    'state' => 'Maharashtra', 'source' => 'cold_call', 'status' => 'new',       'priority' => 'low',    'lead_value' => 35000],

            // Contacted
            ['name' => 'Vikram Singh',      'phone' => '9867452310', 'email' => 'vikram.singh@gmail.com',  'company' => 'Singh Solutions',     'designation' => 'CEO',           'city' => 'Jaipur',    'state' => 'Rajasthan',   'source' => 'facebook',  'status' => 'contacted', 'priority' => 'high',   'lead_value' => 750000,  'contacted_at' => now()->subDays(3)],
            ['name' => 'Anjali Desai',      'phone' => '9876001122', 'email' => 'anjali.desai@gmail.com',  'company' => 'Desai Tech',          'designation' => 'CTO',           'city' => 'Bangalore', 'state' => 'Karnataka',   'source' => 'linkedin',  'status' => 'contacted', 'priority' => 'medium', 'lead_value' => 320000,  'contacted_at' => now()->subDays(5)],
            ['name' => 'Manoj Kumar',       'phone' => '9654321098', 'email' => 'manoj.k@outlook.com',     'company' => 'Kumar Corp',          'designation' => 'VP Sales',      'city' => 'Hyderabad', 'state' => 'Telangana',   'source' => 'email',     'status' => 'contacted', 'priority' => 'low',    'lead_value' => 125000,  'contacted_at' => now()->subDays(7)],

            // Qualified
            ['name' => 'Deepak Agarwal',    'phone' => '9711223344', 'email' => 'deepak.ag@gmail.com',     'company' => 'Agarwal Realty',      'designation' => 'MD',            'city' => 'Lucknow',   'state' => 'UP',          'source' => 'referral',  'status' => 'qualified', 'priority' => 'high',   'lead_value' => 1200000, 'contacted_at' => now()->subDays(10)],
            ['name' => 'Pooja Nair',        'phone' => '9922334455', 'email' => 'pooja.nair@gmail.com',    'company' => 'Nair Consultancy',    'designation' => 'Consultant',    'city' => 'Kochi',     'state' => 'Kerala',      'source' => 'website',   'status' => 'qualified', 'priority' => 'medium', 'lead_value' => 280000,  'contacted_at' => now()->subDays(8)],
            ['name' => 'Amit Saxena',       'phone' => '9833445566', 'email' => 'amit.saxena@yahoo.com',   'company' => 'Saxena & Co.',        'designation' => 'Proprietor',    'city' => 'Agra',      'state' => 'UP',          'source' => 'walk_in',   'status' => 'qualified', 'priority' => 'high',   'lead_value' => 560000,  'contacted_at' => now()->subDays(6)],

            // Converted
            ['name' => 'Neha Kapoor',       'phone' => '9744556677', 'email' => 'neha.kapoor@gmail.com',   'company' => 'Kapoor Fashion',      'designation' => 'Owner',         'city' => 'Chandigarh','state' => 'Punjab',      'source' => 'instagram', 'status' => 'converted', 'priority' => 'high',   'lead_value' => 390000,  'contacted_at' => now()->subDays(20), 'converted_at' => now()->subDays(5)],
            ['name' => 'Suresh Iyer',       'phone' => '9655667788', 'email' => 'suresh.iyer@gmail.com',   'company' => 'Iyer Software',       'designation' => 'Founder',       'city' => 'Chennai',   'state' => 'Tamil Nadu',  'source' => 'google',    'status' => 'converted', 'priority' => 'medium', 'lead_value' => 680000,  'contacted_at' => now()->subDays(15), 'converted_at' => now()->subDays(3)],

            // Lost
            ['name' => 'Gaurav Bhatt',      'phone' => '9566778899', 'email' => 'gaurav.bhatt@gmail.com',  'company' => 'Bhatt Electronics',   'designation' => 'GM',            'city' => 'Bhopal',    'state' => 'MP',          'source' => 'cold_call', 'status' => 'lost',      'priority' => 'medium', 'lead_value' => 210000,  'lost_reason' => 'Budget constraint — competitor offered lower price.'],
            ['name' => 'Rina Pillai',       'phone' => '9477889900', 'email' => 'rina.pillai@outlook.com', 'company' => null,                  'designation' => null,            'city' => 'Trivandrum','state' => 'Kerala',      'source' => 'facebook',  'status' => 'lost',      'priority' => 'low',    'lead_value' => 45000,   'lost_reason' => 'No longer interested.'],
        ];

        foreach ($leads as $i => $data) {
            Lead::create(array_merge($data, [
                'tenant_id'  => $tenantId,
                'created_by' => $createdBy,
                'assigned_to' => $assignees[$i % count($assignees)],
                'notes'      => $data['notes'] ?? null,
            ]));
        }

        $this->command->info('LeadSeeder: ' . count($leads) . ' leads created.');
    }
}
