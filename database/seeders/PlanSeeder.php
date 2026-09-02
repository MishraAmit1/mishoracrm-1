<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    /**
     * Seed the four standard plans. Idempotent — keyed on `slug`, so it can be
     * re-run after a config/modules.php change to re-sync module entitlements.
     *
     * Notes
     *  - There is no per-plan "leads" limit: leads/contacts are unlimited on
     *    every plan and the app never enforced a cap.
     *  - `users`  -1 = unlimited (enforced in StaffController).
     *  - `trial_days` — free days a new signup gets. 0 = no trial (the ₹0 plan
     *    stays permanently free; a paid plan goes straight to checkout). The
     *    superadmin tunes this per plan in the plan editor; these are just the
     *    starting values (they preserve the previous behaviour: paid = 14 days).
     *  - Premium modules come from config/modules.php by `tier` (see helper).
     *  - Enterprise is a `is_custom` plan: no price, no self-serve checkout —
     *    the pricing page shows "Custom" + a "Talk to sales" button.
     */
    public function run(): void
    {
        $modules = config('modules');

        // Module keys included from a given tier upwards.
        $starterModules = $this->modulesForTiers($modules, ['starter']);
        $allModules     = $this->modulesForTiers($modules, ['starter', 'pro']);

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
                'description'   => 'Everything a growing team needs — WhatsApp campaigns, reports, social lead capture and the essential add-on modules.',
                'monthly_price' => 999,
                'yearly_price'  => 9999,
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
                'description'   => 'The full platform — unlimited team members and every premium module, including Manufacturing, Subscriptions and Customer Loyalty.',
                'monthly_price' => 2499,
                'yearly_price'  => 24999,
                'is_custom'     => false,
                'trial_days'    => 14,
                'sort_order'    => 2,
                'features'      => array_merge([
                    'users'             => -1,
                    'whatsapp'          => true,
                    'reports'           => true,
                    'social_leads'      => true,
                    'lead_integrations' => true,
                ], $allModules),
            ],
            [
                'name'          => 'Enterprise',
                'slug'          => 'enterprise',
                'description'   => 'For larger teams — volume pricing, guided onboarding, priority SLA support and a dedicated account manager.',
                'monthly_price' => 0,
                'yearly_price'  => 0,
                'is_custom'     => true,
                'trial_days'    => 0,
                'sort_order'    => 3,
                'features'      => array_merge([
                    'users'             => -1,
                    'whatsapp'          => true,
                    'reports'           => true,
                    'social_leads'      => true,
                    'lead_integrations' => true,
                ], $allModules),
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
