<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\WhatsappSetting;
use Illuminate\Support\Facades\Mail;

// Customer-facing Email/WhatsApp for tickets — always sent (transactional,
// not gated by any opt-in toggle), regardless of whether the ticket was
// created via the public form or manually by staff. Not routed through
// NotificationService since a Contact isn't a User — same reasoning as
// SubscriptionReminderService / Public\AppointmentController::sendConfirmation.
class TicketNotificationService
{
    public const DEFAULT_EMAIL_SUBJECT = 'Ticket {{ticket_number}} received — {{tenant_name}}';

    public const DEFAULT_EMAIL_BODY = "<p>Dear {{contact_name}},</p>\n"
        . "<p>We've received your support request. Your ticket number is <strong>{{ticket_number}}</strong> — please quote this if you contact us again about it.</p>\n"
        . "<p><strong>Subject:</strong> {{subject}}</p>\n"
        . "<p>Our support team will review it and get back to you soon.</p>\n"
        . "<p><a href=\"{{tracking_link}}\">Track your ticket / view updates</a></p>\n"
        . "<p>Thank you,<br>{{tenant_name}}</p>";

    public const DEFAULT_WHATSAPP_BODY = 'Hi {{contact_name}}, we\'ve received your support request. Your ticket number is {{ticket_number}} — "{{subject}}". '
        . 'Our team will review it and get back to you soon. Track: {{tracking_link}}';

    // Sample data shown in the live preview / used for "send test" —
    // recognizable as placeholder content, never a real customer's data.
    public const SAMPLE_DATA = [
        'contact_name' => 'Ramesh Kumar',
        'ticket_number' => 'TKT-20260822-0001',
        'subject' => 'Unable to login to my account',
    ];

    // Sent once, immediately after a ticket is created — "we got your
    // request, here's your ticket number and a link to track it."
    // $channels controls which methods to attempt: any of ['email','whatsapp'];
    // null (default) means "respect the tenant's saved channel preference"
    // (see Tenant::wantsTicketConfirmation) — used for public self-submitted
    // tickets and whenever staff don't explicitly override per ticket.
    public static function sendConfirmation(Ticket $ticket, ?array $channels = null): void
    {
        $contact = $ticket->contact;
        $tenant  = $ticket->tenant;
        if (!$contact || !$tenant) {
            return;
        }

        if ($channels === null) {
            $channels = [];
            if ($tenant->wantsTicketConfirmation('email')) {
                $channels[] = 'email';
            }
            if ($tenant->wantsTicketConfirmation('whatsapp')) {
                $channels[] = 'whatsapp';
            }
        }

        $link = $ticket->publicUrl();
        $data = [
            'contact_name'  => $contact->name,
            'ticket_number' => $ticket->ticket_number,
            'subject'       => $ticket->subject,
            'tenant_name'   => $tenant->name,
            'tracking_link' => $link,
        ];

        if (in_array('email', $channels, true) && $contact->email) {
            $subjectTemplate = $tenant->ticketConfirmationTemplate('email_subject') ?? static::DEFAULT_EMAIL_SUBJECT;
            $bodyTemplate    = $tenant->ticketConfirmationTemplate('email_body') ?? static::DEFAULT_EMAIL_BODY;

            $subject = static::substitute($subjectTemplate, $data);
            $html    = static::substitute($bodyTemplate, $data);

            static::email($tenant->id, $contact->email, $contact->name, $subject, $html);
        }

        if (in_array('whatsapp', $channels, true) && $contact->phone) {
            $bodyTemplate = $tenant->ticketConfirmationTemplate('whatsapp_body') ?? static::DEFAULT_WHATSAPP_BODY;
            $message = static::substitute($bodyTemplate, $data);

            static::whatsapp($tenant->id, $contact->phone, $message);
        }
    }

    // Sends the CURRENT (possibly unsaved) template text to a single test
    // recipient using sample placeholder data — lets staff verify actual
    // deliverability before saving a template that will go out to real
    // customers. Mirrors SubscriptionReminderService::sendTestEmail.
    public static function sendTestEmail(int $tenantId, string $toEmail, string $toName, string $subjectTemplate, string $bodyTemplate): bool
    {
        $data = static::SAMPLE_DATA + [
            'tenant_name'   => auth()->user()->tenant->name ?? 'Your Business',
            'tracking_link' => url('/support/ticket/sample-token'),
        ];

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

    // Sent whenever staff post a non-internal reply — lets the customer
    // know without needing to keep the tracking page open.
    public static function sendReply(Ticket $ticket, string $replyBody): void
    {
        $contact = $ticket->contact;
        if (!$contact) {
            return;
        }

        $tenant = $ticket->tenant;
        $link   = $ticket->publicUrl();

        if ($contact->email) {
            $subject = "Re: Ticket {$ticket->ticket_number} — {$ticket->subject}";
            $html = "<p>Dear {$contact->name},</p>"
                . "<p>{$tenant->name} replied to your ticket <strong>{$ticket->ticket_number}</strong>:</p>"
                . "<p style=\"padding:10px;background:#f5f5f5;border-radius:6px\">" . nl2br(e($replyBody)) . "</p>"
                . "<p><a href=\"{$link}\">View or reply to this ticket</a></p>";

            static::email($tenant->id, $contact->email, $contact->name, $subject, $html);
        }

        if ($contact->phone) {
            $message = "Hi {$contact->name}, {$tenant->name} replied to your ticket {$ticket->ticket_number}: {$replyBody}\n\nView: {$link}";
            static::whatsapp($tenant->id, $contact->phone, $message);
        }
    }

    private static function email(int $tenantId, string $toEmail, string $toName, string $subject, string $html): void
    {
        try {
            $sent = EmailService::send($tenantId, $toEmail, $toName, $subject, $html);

            if (!$sent) {
                Mail::send([], [], function ($mail) use ($toEmail, $toName, $subject, $html) {
                    $mail->to($toEmail, $toName)->subject($subject)->html($html);
                });
            }
        } catch (\Throwable $e) {
            // best-effort — the ticket/reply itself is already saved
        }
    }

    private static function whatsapp(int $tenantId, string $phone, string $message): void
    {
        $settings = WhatsappSetting::forTenant($tenantId);
        if (!$settings->exists || !$settings->is_connected) {
            return;
        }

        try {
            $waId = preg_replace('/[^0-9]/', '', $phone);
            WhatsappChatbotService::forTenant($tenantId)->sendMessage($waId, $message);
        } catch (\Throwable $e) {
            // best-effort
        }
    }
}
