<?php

// app/Models/Tenant.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Tenant extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'subdomain',
        'email',
        'phone',
        'logo',
        'timezone',
        'currency',
        'status',
        'settings'
    ];

    protected $casts = [
        'settings' => 'array',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function subscription()
    {
        return $this->hasOne(Subscription::class)->latestOfMany();
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function getWebhookToken(): string
    {
        $settings = $this->settings ?? [];

        if (empty($settings['webhook_token'])) {
            $token = 'crm_whk_' . Str::random(40);
            $settings['webhook_token'] = $token;
            $this->update(['settings' => $settings]);
        }

        return $settings['webhook_token'];
    }

    public function regenerateWebhookToken(): string
    {
        $settings = $this->settings ?? [];
        $token = 'crm_whk_' . Str::random(40);
        $settings['webhook_token'] = $token;
        $this->update(['settings' => $settings]);

        return $token;
    }

    public function deviceTokens()
    {
        return $this->hasMany(DeviceToken::class);
    }

    // ── Module access — plan entitlement, with a full superadmin override ──
    // Three states, checked in order:
    //   1. settings['modules'][$module] === true  → force ON  (superadmin override)
    //   2. settings['modules'][$module] === false → force OFF (superadmin override)
    //   3. key absent/null                        → inherit from the Plan
    //      (Plan.features[$module] === true, e.g. sold as a Pro-tier perk)
    // The override is a tri-state, not a plain boolean OR: superadmin can
    // grant access on a plan that doesn't include it, AND revoke access on
    // a plan that does — same settings['modules'][...] convention as
    // settings['integrations'][$platform] (see TenantIntegration), just
    // with "unset" now meaningfully distinct from "false".
    public function hasModuleEnabled(string $module): bool
    {
        $override = $this->moduleOverride($module);

        if ($override !== null) {
            return $override;
        }

        return (bool) $this->subscription?->plan?->hasFeature($module);
    }

    // Null = no superadmin override set for this tenant (inheriting from plan).
    // true/false = superadmin has explicitly forced it on/off for this tenant.
    public function moduleOverride(string $module): ?bool
    {
        if (!array_key_exists($module, $this->settings['modules'] ?? [])) {
            return null;
        }

        return (bool) $this->settings['modules'][$module];
    }

    // True when the tenant's plan itself grants the module (used by the
    // superadmin UI to distinguish "included in plan" from "manually granted").
    public function moduleIncludedInPlan(string $module): bool
    {
        return (bool) $this->subscription?->plan?->hasFeature($module);
    }
}
