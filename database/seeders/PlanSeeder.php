<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    /**
     * Seed the five standard plans. Idempotent — keyed on `slug`, so it can be
     * re-run after a config/modules.php change to re-sync module entitlements.
     *
     * Pricing ladder (yearly, self-serve checkout):
     *   Free       ₹0
     *   Starter    ₹15,000
     *   Pro        ₹23,999  (Most Popular)
     *   Business   ₹49,999  (top fixed-price tier)
     *   Enterprise Custom — no self-serve price, "Talk to sales"
     *
     * Notes
     *  - There is no per-plan "leads" limit: leads/contacts are unlimited on
     *    every plan and the app never enforced a cap.
     *  - `users` is always a concrete cap — no plan (including Enterprise)
     *    uses -1/unlimited; superadmin can raise a tenant's cap individually
     *    from the tenant detail screen if a contract needs more.
     *  - `trial_days` — free days a new signup gets. 0 = no trial (the ₹0 plan
     *    stays permanently free; a paid plan goes straight to checkout).
     *  - Premium modules come from config/modules.php by `tier` (see helper).
     *  - Enterprise is a `is_custom` plan: no price, no self-serve checkout —
     *    the pricing page shows "Custom" + a "Talk to sales" button.
     */
    public function run(): void
    {
        $modules = config('modules');

        // Module keys included from a given tier upwards.
        $starterModules  = $this->modulesForTiers($modules, ['starter']);
        $proModules      = $this->modulesForTiers($modules, ['starter', 'pro']);
        $businessModules = $this->modulesForTiers($modules, ['starter', 'pro', 'business']);

        $plans = [
            [
                'name'          => 'Free',
                'slug'          => 'free',
                'description'   => 'Get started with the core CRM — leads, deals, tasks and GST invoicing.',
                'monthly_price' => 0,
                'yearly_price'  => 0,
                'is_custom'     => false,
                'trial_days'    => 0,
                'sort_order'    => 0,
                'features'      => [
                    'users'    => 2,
                    'whatsapp' => false,
                    'reports'  => false,
                ],
            ],
            [
                'name'          => 'Starter',
                'slug'          => 'starter',
                'description'   => 'For small teams getting serious — WhatsApp campaigns, reports, social lead capture and the essential add-on modules.',
                'monthly_price' => 1500,
                'yearly_price'  => 15000,
                'is_custom'     => false,
                'trial_days'    => 14,
                'sort_order'    => 1,
                'features'      => array_merge([
                    'users'             => 5,
                    'whatsapp'          => true,
                    'reports'           => true,
                    'social_leads'      => true,
                    'lead_integrations' => true,
                ], $starterModules),
            ],
            [
                'name'          => 'Pro',
                'slug'          => 'pro',
                'description'   => 'Our most popular plan — a bigger team plus Service Catalog and Service Subscriptions.',
                'monthly_price' => 2400,
                'yearly_price'  => 23999,
                'is_custom'     => false,
                'trial_days'    => 14,
                'sort_order'    => 2,
                'features'      => array_merge([
                    'users'             => 15,
                    'whatsapp'          => true,
                    'reports'           => true,
                    'social_leads'      => true,
                    'lead_integrations' => true,
                ], $proModules),
            ],
            [
                'name'          => 'Business',
                'slug'          => 'business',
                'description'   => 'For larger, multi-department teams — everything in Pro plus Manufacturing and Customer Loyalty, and room for up to 50 team members.',
                'monthly_price' => 5000,
                'yearly_price'  => 49999,
                'is_custom'     => false,
                'trial_days'    => 14,
                'sort_order'    => 3,
                'features'      => array_merge([
                    'users'             => 50,
                    'whatsapp'          => true,
                    'reports'           => true,
                    'social_leads'      => true,
                    'lead_integrations' => true,
                ], $businessModules),
            ],
            [
                'name'          => 'Enterprise',
                'slug'          => 'enterprise',
                'description'   => 'For large teams — volume pricing, guided onboarding, priority SLA support and a dedicated account manager.',
                'monthly_price' => 0,
                'yearly_price'  => 0,
                'is_custom'     => true,
                'trial_days'    => 0,
                'sort_order'    => 4,
                'features'      => array_merge([
                    'users'             => 100,
                    'whatsapp'          => true,
                    'reports'           => true,
                    'social_leads'      => true,
                    'lead_integrations' => true,
                ], $businessModules),
            ],
        ];

        foreach ($plans as $plan) {
            Plan::updateOrCreate(
                ['slug' => $plan['slug']],
                array_merge($plan, ['is_active' => true]),
            );
        }
    }

    /**
     * @param  array<string,array{tier?:string}>  $modules
     * @param  list<string>  $tiers
     * @return array<string,bool>  ['tickets' => true, ...]
     */
    private function modulesForTiers(array $modules, array $tiers): array
    {
        $included = [];
        foreach ($modules as $key => $meta) {
            if (\in_array($meta['tier'] ?? 'pro', $tiers, true)) {
                $included[$key] = true;
            }
        }

        return $included;
    }
}
