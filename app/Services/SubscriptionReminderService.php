<?php

namespace App\Services;

use App\Models\ServiceSubscription;
use App\Models\SubscriptionReminderLog;
use App\Models\WhatsappLog;
use App\Models\WhatsappSetting;
use Illuminate\Support\Facades\Mail;

// Shared by the daily automatic reminder command and the manual
// "Send Reminder" button — one place for message templating + delivery so
// both call sites stay in sync. NotificationService can't be reused here:
// it's hard-wired to a User recipient, and a Contact isn't one.
class SubscriptionReminderService
{
    public const DEFAULT_EMAIL_SUBJECT = 'Your {{service_name}} is expiring soon';

    public const DEFAULT_EMAIL_BODY = "<p>Dear {{contact_name}},</p>\n"
        . "<p>This is a reminder that your <strong>{{service_name}}</strong> with {{tenant_name}} is expiring on <strong>{{expiry_date}}</strong>.</p>\n"
        . "<p>Please get in touch with us to renew and avoid any interruption.</p>\n"
        . "<p>Thank you,<br>{{tenant_name}}</p>";

    public const DEFAULT_WHATSAPP_BODY = 'Hi {{contact_name}}, your {{service_name}} with {{tenant_name}} is expiring on {{expiry_date}}. '
        . 'Please reach out to renew and avoid any interruption.';

    // Sample data shown in the live preview / used for "send test" —
    // recognizable as placeholder content, never a real customer's data.
    public const SAMPLE_DATA = [
        'contact_name' => 'Ramesh Kumar',
        'service_name' => 'Annual Membership',
        'expiry_date'  => '25 Aug 2026',
    ];

    // $emailOverride/$whatsappOverride: null = respect the tenant's saved
    // toggle (used by the automatic daily command); true = send regardless
    // of the toggle (used by the manual "Send Reminder" button — an
    // explicit staff action should never be silently skipped).
    // $isManual is recorded on the log row so the history page can show
    // whether a reminder was triggered by staff or by the daily cron.
    public static function send(ServiceSubscription $subscription, ?bool $emailOverride = null, ?bool $whatsappOverride = null, bool $isManual = false): array
    {
        $tenant  = $subscription->tenant;
        $contact = $subscription->contact;
        $service = $subscription->service;

        $result = ['email' => null, 'whatsapp' => null];

        if (!$tenant || !$contact || !$service) {
            return $result;
        }

        $wantEmail    = $emailOverride ?? $tenant->wantsSubscriptionReminder('email');
        $wantWhatsapp = $whatsappOverride ?? $tenant->wantsSubscriptionReminder('whatsapp');

        $data = [
            'contact_name' => $contact->name,
            'service_name' => $service->name,
            'tenant_name'  => $tenant->name,
            'expiry_date'  => $subscription->expires_at?->format('d M Y') ?? '—',
        ];

        if ($wantEmail) {
            $result['email'] = static::sendEmail($tenant, $contact, $data, $subscription, $isManual);
        }

        if ($wantWhatsapp) {
            $result['whatsapp'] = static::sendWhatsapp($tenant, $contact, $data, $subscription, $isManual);
        }

        return $result;
    }

    // Sends the CURRENT (possibly unsaved) template text to a single test
    // recipient using sample placeholder data — lets staff verify actual
    // deliverability (real SMTP, real inbox) before saving a template that
    // will go out to real customers. Not tied to a subscription, so nothing
    // is written to SubscriptionReminderLog.
    public static function sendTestEmail(int $tenantId, string $toEmail, string $toName, string $subjectTemplate, string $bodyTemplate): bool
    {
        $data    = static::SAMPLE_DATA + ['tenant_name' => auth()->user()->tenant->name ?? 'Your Business'];
        $subject = '[TEST] ' . static::substitute($subjectTemplate, $data);
        $html    = static::substitute($bodyTemplate, $data);

        try {
            $sent = EmailService::send($tenantId, $toEmail, $toName, $subject, $html);

            if (!$sent) {
                Mail::send([], [], function ($mail) use ($toEmail, $toName, $subject, $html) {
                    $mail->to($toEmail, $toName)->subject($subject)->html($html);
                });
                $sent = true;
            }

            return $sent;
        } catch (\Throwable $e) {
            return false;
        }
    }

    public static function substitute(string $template, array $data): string
    {
        $pairs = [];
        foreach ($data as $key => $value) {
            $pairs['{{' . $key . '}}'] = $value;
        }

        return strtr($template, $pairs);
    }

    private static function sendEmail($tenant, $contact, array $data, ServiceSubscription $subscription, bool $isManual): ?bool
    {
        $toEmail = $contact->primaryEmail();
        if (!$toEmail) {
            return null;
        }

        $subjectTemplate = $tenant->subscriptionReminderTemplate('email_subject') ?? static::DEFAULT_EMAIL_SUBJECT;
        $bodyTemplate    = $tenant->subscriptionReminderTemplate('email_body') ?? static::DEFAULT_EMAIL_BODY;

        $subject = static::substitute($subjectTemplate, $data);
        $html    = static::substitute($bodyTemplate, $data);
        $error   = null;

        try {
            $sent = EmailService::send($tenant->id, $toEmail, $contact->name, $subject, $html);

            if (!$sent) {
                Mail::send([], [], function ($mail) use ($toEmail, $contact, $subject, $html) {
                    $mail->to($toEmail, $contact->name)->subject($subject)->html($html);
                });
                $sent = true;
            }
        } catch (\Throwable $e) {
            $sent  = false;
            $error = $e->getMessage();
        }

        SubscriptionReminderLog::create([
            'tenant_id'                => $tenant->id,
            'service_subscription_id'  => $subscription->id,
            'contact_id'               => $contact->id,
            'channel'                  => 'email',
            'status'                   => $sent ? 'sent' : 'failed',
            'message'                  => $subject . "\n\n" . $html,
            'error_message'            => $error,
            'is_manual'                => $isManual,
            'sent_at'                  => now(),
        ]);

        return $sent;
    }

    private static function sendWhatsapp($tenant, $contact, array $data, ServiceSubscription $subscription, bool $isManual): ?bool
    {
        if (!$contact->phone) {
            return null;
        }

        $settings = WhatsappSetting::forTenant($tenant->id);
        if (!$settings->exists || !$settings->is_connected) {
            return null;
        }

        $bodyTemplate = $tenant->subscriptionReminderTemplate('whatsapp_body') ?? static::DEFAULT_WHATSAPP_BODY;
        $message      = static::substitute($bodyTemplate, $data);
        $waId         = preg_replace('/[^0-9]/', '', $contact->phone);
        $error        = null;

        try {
            $ok = WhatsappChatbotService::forTenant($tenant->id)->sendMessage($waId, $message);
        } catch (\Throwable $e) {
            $ok    = false;
            $error = $e->getMessage();
        }

        WhatsappLog::create([
            'tenant_id'  => $tenant->id,
            'contact_id' => $contact->id,
            'to_phone'   => $contact->phone,
            'to_name'    => $contact->name,
            'message'    => $message,
            'status'     => $ok ? 'sent' : 'failed',
            'sent_at'    => now(),
        ]);

        SubscriptionReminderLog::create([
            'tenant_id'                => $tenant->id,
            'service_subscription_id'  => $subscription->id,
            'contact_id'               => $contact->id,
            'channel'                  => 'whatsapp',
            'status'                   => $ok ? 'sent' : 'failed',
            'message'                  => $message,
            'error_message'            => $error,
            'is_manual'                => $isManual,
            'sent_at'                  => now(),
        ]);

        return $ok;
    }
}
