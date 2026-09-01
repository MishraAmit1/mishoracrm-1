<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\EmailLog;
use App\Models\WhatsappLog;
use App\Models\WhatsappSetting;

// Fire-and-forget customer greetings for the Loyalty module (birthday /
// anniversary). Sends over WhatsApp when the tenant has it connected, and/or
// Email — whichever the contact can receive. Never throws.
class LoyaltyNotifier
{
    public const DEFAULTS = [
        'birthday_message'    => 'Happy Birthday {{contact_name}}! 🎂 {{tenant_name}} has added {{points}} loyalty points to your account — you now have {{balance}}. See you soon!',
        'anniversary_message' => 'Happy Anniversary {{contact_name}}! 🎉 {{tenant_name}} has added {{points}} loyalty points — your balance is now {{balance}}.',
    ];

    public static function sample(): array
    {
        return [
            'contact_name' => 'Ramesh Kumar',
            'points'       => '100',
            'balance'      => '340',
            'tenant_name'  => 'Your Business',
        ];
    }

    // Proactive "you earned points" nudge after a visit / referral. Opt-in per
    // tenant via settings['loyalty']['notify_customers'].
    public static function pointsAwarded(Contact $contact, int $points, string $reason): void
    {
        $tenant = $contact->tenant;
        if ($points <= 0 || !$tenant || !($tenant->loyaltySettings()['notify_customers'] ?? false)) {
            return;
        }

        $s     = $tenant->loyaltySettings();
        $block = (int) $s['redeem_points_block'];
        $value = $block > 0 ? floor((int) $contact->loyalty_points / $block) * (float) $s['redeem_value'] : 0;

        $message = "Hi {$contact->name}! You earned " . number_format($points) . " loyalty points ({$reason}). "
            . 'Balance: ' . number_format((int) $contact->loyalty_points)
            . ($value > 0 ? ' — worth ₹' . number_format($value, 0) . ' off your next visit at ' . $tenant->name . '.' : '.');

        self::viaWhatsapp($contact, $message);
        self::viaEmail($contact, "You earned loyalty points at {$tenant->name}", $message);
    }

    // "Your points expire soon" nudge. Always sends (configuring
    // expiry_reminder_days is the opt-in).
    public static function expiryReminder(Contact $contact, int $points, \Carbon\CarbonInterface $on): void
    {
        $tenant = $contact->tenant;
        if ($points <= 0 || !$tenant) {
            return;
        }

        $message = "Hi {$contact->name}! " . number_format($points)
            . " of your {$tenant->name} loyalty points expire on " . $on->format('d M Y')
            . '. Visit us soon to use them before they lapse!';

        self::viaWhatsapp($contact, $message);
        self::viaEmail($contact, "Your {$tenant->name} points expire soon", $message);
    }

    // $occasion: 'birthday' | 'anniversary'. $points = amount just granted.
    public static function greet(Contact $contact, string $occasion, int $points): void
    {
        $tenant = $contact->tenant;
        if (!$tenant) {
            return;
        }

        $template = $tenant->loyaltySettings()["{$occasion}_message"]
            ?? self::DEFAULTS["{$occasion}_message"];

        $message = strtr($template, [
            '{{contact_name}}' => $contact->name,
            '{{points}}'       => number_format($points),
            '{{balance}}'      => number_format((int) $contact->loyalty_points),
            '{{tenant_name}}'  => $tenant->name,
        ]);

        self::viaWhatsapp($contact, $message);
        self::viaEmail($contact, ucfirst($occasion) . " reward from {$tenant->name}", $message);
    }

    private static function viaWhatsapp(Contact $contact, string $message): void
    {
        if (!$contact->phone) {
            return;
        }

        $settings = WhatsappSetting::forTenant($contact->tenant_id);
        if (!$settings->exists || !$settings->is_connected) {
            return;
        }

        $ok = false;
        try {
            $waId = preg_replace('/[^0-9]/', '', $contact->phone);
            $ok   = WhatsappChatbotService::forTenant($contact->tenant_id)->sendMessage($waId, $message);
        } catch (\Throwable $e) {
            $ok = false;
        }

        WhatsappLog::create([
            'tenant_id'  => $contact->tenant_id,
            'contact_id' => $contact->id,
            'to_phone'   => $contact->phone,
            'to_name'    => $contact->name,
            'message'    => $message,
            'status'     => $ok ? 'sent' : 'failed',
            'sent_at'    => now(),
        ]);
    }

    private static function viaEmail(Contact $contact, string $subject, string $message): void
    {
        $to = $contact->primaryEmail();
        if (!$to) {
            return;
        }

        $html = '<p>' . e($message) . '</p>';
        $ok   = false;

        try {
            $ok = EmailService::send($contact->tenant_id, $to, $contact->name, $subject, $html);
        } catch (\Throwable $e) {
            $ok = false;
        }

        EmailLog::create([
            'tenant_id'  => $contact->tenant_id,
            'contact_id' => $contact->id,
            'to_email'   => $to,
            'to_name'    => $contact->name,
            'subject'    => $subject,
            'body'       => $html,
            'status'     => $ok ? 'sent' : 'failed',
            'sent_at'    => now(),
        ]);
    }
}
