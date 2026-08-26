<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\WhatsappSetting;
use Illuminate\Support\Facades\Mail;

// "Your appointment is coming up" — sent by the appointments:remind-upcoming
// cron a fixed window before starts_at, and the same-day "today at X" note
// sent by appointments:remind-today. Both gated by
// Tenant::wantsAppointmentNotifications() (opt-in, off by default). Not
// routed through NotificationService since a Contact isn't a User.
class AppointmentReminderService
{
    public static function send(Appointment $appointment): void
    {
        $contact = $appointment->contact;
        $tenant  = $appointment->tenant;
        $service = $appointment->service;
        if (!$contact || !$tenant || !$service || !$tenant->wantsAppointmentNotifications()) {
            return;
        }

        $when = $appointment->starts_at->format('d M Y, h:i A');
        $link = $appointment->publicUrl();

        if ($contact->email) {
            $subject = "Reminder: your appointment is coming up — {$service->name}";
            $html = "<p>Dear {$contact->name},</p>"
                . "<p>This is a reminder that your appointment for <strong>{$service->name}</strong> with {$tenant->name} is on <strong>{$when}</strong>.</p>"
                . "<p><a href=\"{$link}\">View or cancel your booking</a></p>"
                . "<p>Thank you,<br>{$tenant->name}</p>";

            static::email($tenant->id, $contact->email, $contact->name, $subject, $html);
        }

        if ($contact->phone) {
            $message = "Hi {$contact->name}, reminder: your appointment for {$service->name} with {$tenant->name} is on {$when}. Manage: {$link}";
            static::whatsapp($tenant->id, $contact->phone, $message);
        }
    }

    // ── Same-day reminder — "you have an appointment today at H:MM" —
    // sent once each morning for every appointment landing later that day.
    // Separate from send() above (24h-before window): this is an additional
    // notice, not a replacement.
    public static function sendDayOf(Appointment $appointment): void
    {
        $contact = $appointment->contact;
        $tenant  = $appointment->tenant;
        $service = $appointment->service;
        if (!$contact || !$tenant || !$service || !$tenant->wantsAppointmentNotifications()) {
            return;
        }

        $time = $appointment->starts_at->format('h:i A');
        $link = $appointment->publicUrl();

        if ($contact->email) {
            $subject = "Today's appointment reminder — {$service->name} at {$time}";
            $html = "<p>Dear {$contact->name},</p>"
                . "<p>This is a reminder that you have an appointment today for <strong>{$service->name}</strong> with {$tenant->name} at <strong>{$time}</strong>.</p>"
                . "<p><a href=\"{$link}\">View or cancel your booking</a></p>"
                . "<p>Thank you,<br>{$tenant->name}</p>";

            static::email($tenant->id, $contact->email, $contact->name, $subject, $html);
        }

        if ($contact->phone) {
            $message = "Hi {$contact->name}, reminder: your appointment for {$service->name} with {$tenant->name} is today at {$time}. Manage: {$link}";
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
            // best-effort — reminder failing shouldn't break the cron run
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
