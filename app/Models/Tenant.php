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

    // Company's registered state (code) and GSTIN, from company settings —
    // used as the supplier side for GST place-of-supply on sales docs and
    // the recipient side on purchase docs.
    public function companyState(): ?string
    {
        return $this->settings['state'] ?? null;
    }

    public function gstin(): ?string
    {
        return $this->settings['gst'] ?? null;
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

    // Appointment notifications (booking confirmation + reminders) to the
    // Contact — opt-in, off by default, same convention as
    // wantsSubscriptionReminder(). Explicitly requested as "only send once
    // the tenant turns it on", unlike the ticket-confirmation default-on
    // exception above.
    public function wantsAppointmentNotifications(): bool
    {
        return (bool) ($this->settings['preferences']['appointment_notifications'] ?? false);
    }

    // Manufacturing — when ON, a Work Order that can't complete due to a
    // raw material shortfall auto-creates a Purchase Request for the
    // shortage instead of just blocking with an error. Off by default so
    // behavior is unchanged until the tenant owner opts in.
    public function wantsAutoCreatePurchaseRequestOnShortfall(): bool
    {
        return (bool) ($this->settings['preferences']['auto_create_pr_on_shortfall'] ?? false);
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

    // ── Public "check my rewards" link — same token convention. Only
    // usable when the Loyalty module is on AND the tenant opted in via
    // settings['loyalty']['public_lookup']. ─────────────────────────
    public function ensureRewardsToken(): string
    {
        $settings = $this->settings ?? [];

        if (empty($settings['rewards_token'])) {
            $settings['rewards_token'] = Str::random(40);
            $this->update(['settings' => $settings]);
        }

        return $settings['rewards_token'];
    }

    public function rewardsPublicUrl(): string
    {
        return route('public.rewards.show', $this->ensureRewardsToken());
    }

    public function loyaltyPublicLookupEnabled(): bool
    {
        return $this->hasModuleEnabled('loyalty')
            && (bool) ($this->loyaltySettings()['public_lookup'] ?? false);
    }

    // The wa.me link a customer follows to auto-join the loyalty programme,
    // or null when the tenant hasn't set up the QR welcome feature.
    public function loyaltyWelcomeUrl(): ?string
    {
        $s      = $this->loyaltySettings();
        $number = preg_replace('/\D/', '', (string) ($s['welcome_wa_number'] ?? ''));

        if ((int) $s['welcome_bonus_points'] <= 0 || $number === '') {
            return null;
        }

        return 'https://wa.me/' . $number . '?text=' . rawurlencode($s['welcome_keyword'] ?: 'JOIN');
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

    // ── Customer Loyalty rules — settings['loyalty'][...], tenant-editable
    // via the Loyalty Settings page. Same defaults-merge pattern as
    // bookingSettings(). Gate the feature itself with hasModuleEnabled('loyalty');
    // this only holds the numeric rules. ────────────────────────────────
    public const LOYALTY_DEFAULTS = [
        'points_per_amount'     => 1,     // points earned ...
        'amount_per_point_block' => 100,  // ... per this much spent (paid)
        'redeem_points_block'   => 100,   // this many points ...
        'redeem_value'          => 10,    // ... equal this much discount
        'min_discount'          => 0,     // floor on a redemption, per bill
        'max_discount_percent'  => 20,    // cap on a redemption, % of the bill
        'expiry_months'         => 12,    // earned points lapse after N months (0 = never)
        'max_points_per_day'    => 500,   // anti-abuse: max points one contact can earn per day
        'tiers'                 => [
            'bronze' => 0,
            'silver' => 2000,
            'gold'   => 10000,
        ],
        // ── Engagement (Phase 3) — 0 = that feature is off ──────────
        'birthday_bonus_points'    => 0,   // points gifted on a customer's birthday
        'anniversary_bonus_points' => 0,   // points gifted on their anniversary
        'referral_bonus_points'    => 0,   // points to BOTH sides when a referral is linked
        'inactive_days'            => 30,  // "lapsed" cut-off for the win-back list
        'expiry_reminder_days'     => 7,   // warn the customer this many days before points lapse (0 = off)
        // ── Flash / double-points days ─────────────────────────────
        'multiplier'      => 1,            // points multiplier on the days below
        'multiplier_days' => [],           // weekday keys: mon tue wed thu fri sat sun
        // ── Standing reward catalog — [{name, points}] — "500 pts = 1 free coffee" ──
        'reward_catalog'  => [],
        // ── Customer messaging (opt-in) ────────────────────────────
        'notify_customers'    => false,    // WhatsApp/Email the customer after each points earn
        'whatsapp_self_check' => false,    // reply to "points"/"balance" keywords on WhatsApp
        'public_lookup'       => false,    // enable the OTP-guarded "check my rewards" page
        // ── WhatsApp "scan to join" welcome capture (§2a) ──────────
        'welcome_bonus_points' => 0,       // points a first-time WhatsApp joiner gets (0 = off)
        'welcome_keyword'      => 'JOIN',  // the wa.me prefill text customers send
        'welcome_wa_number'    => '',      // the tenant's WhatsApp Business number (digits)
        'welcome_message'      => 'Welcome to {{tenant_name}} rewards, {{contact_name}}! 🎉 You have {{points}} points to start.',
    ];

    public function loyaltySettings(): array
    {
        return array_replace_recursive(self::LOYALTY_DEFAULTS, $this->settings['loyalty'] ?? []);
    }
}
