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

    // ── Tenant-controlled preferences (opt-in, off by default) ──────
    // settings['preferences'][$key] — same array-in-JSON convention as
    // settings['modules']/settings['integrations'], but tenant-admin
    // self-service rather than superadmin-controlled.
    public function wantsSubscriptionReminder(string $channel): bool
    {
        return (bool) ($this->settings['preferences']["subscription_reminder_{$channel}"] ?? false);
    }

    // How many days before expiry the automatic reminder should fire.
    public function subscriptionReminderDays(): int
    {
        $days = (int) ($this->settings['preferences']['subscription_reminder_days'] ?? 7);

        return $days > 0 ? $days : 7;
    }

    // Tenant's custom message template for a channel, or null to use the
    // built-in default (see SubscriptionReminderService::DEFAULT_*).
    public function subscriptionReminderTemplate(string $key): ?string
    {
        $value = $this->settings['preferences']["subscription_reminder_{$key}"] ?? null;

        return is_string($value) && trim($value) !== '' ? $value : null;
    }

    // Ticket confirmation channel preference — unlike subscription reminders
    // this defaults ON (true) for an unset key, since the confirmation was
    // originally always-sent and tenants shouldn't lose it silently just
    // because they've never visited the settings panel.
    public function wantsTicketConfirmation(string $channel): bool
    {
        $value = $this->settings['preferences']["ticket_confirmation_{$channel}"] ?? null;

        return $value === null ? true : (bool) $value;
    }

    // Tenant's custom ticket-confirmation template for a channel, or null to
    // use the built-in default (see TicketNotificationService::DEFAULT_*).
    public function ticketConfirmationTemplate(string $key): ?string
    {
        $value = $this->settings['preferences']["ticket_confirmation_{$key}"] ?? null;

        return is_string($value) && trim($value) !== '' ? $value : null;
    }

    // ── Public booking link — same settings['...'] token convention as
    // getWebhookToken() above, so no new tenants column is needed. ──────
    public function ensureBookingToken(): string
    {
        $settings = $this->settings ?? [];

        if (empty($settings['booking_token'])) {
            $settings['booking_token'] = Str::random(40);
            $this->update(['settings' => $settings]);
        }

        return $settings['booking_token'];
    }

    public function bookingPublicUrl(): string
    {
        return route('public.booking.show', $this->ensureBookingToken());
    }

    // ── Public support-ticket submission link — same pattern as the
    // booking token above. ──────────────────────────────────────────
    public function ensureSupportToken(): string
    {
        $settings = $this->settings ?? [];

        if (empty($settings['support_token'])) {
            $settings['support_token'] = Str::random(40);
            $this->update(['settings' => $settings]);
        }

        return $settings['support_token'];
    }

    public function supportPublicUrl(): string
    {
        return route('public.support.show', $this->ensureSupportToken());
    }

    // settings['booking'][...] — configured via the tenant's Appointments
    // Settings page. Defaults keep booking OFF until the tenant opts in.
    public function bookingSettings(): array
    {
        $defaults = [
            'enabled'               => false,
            'slot_duration_minutes' => 30,
            'capacity_per_slot'     => 1,
            'advance_booking_days'  => 14,
            'hours'                 => [
                'mon' => ['closed' => false, 'open' => '09:00', 'close' => '18:00'],
                'tue' => ['closed' => false, 'open' => '09:00', 'close' => '18:00'],
                'wed' => ['closed' => false, 'open' => '09:00', 'close' => '18:00'],
                'thu' => ['closed' => false, 'open' => '09:00', 'close' => '18:00'],
                'fri' => ['closed' => false, 'open' => '09:00', 'close' => '18:00'],
                'sat' => ['closed' => false, 'open' => '09:00', 'close' => '18:00'],
                'sun' => ['closed' => true,  'open' => '09:00', 'close' => '18:00'],
            ],
        ];

        return array_replace_recursive($defaults, $this->settings['booking'] ?? []);
    }
}
