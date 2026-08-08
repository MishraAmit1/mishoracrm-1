<?php

namespace App\Services;

use App\Models\EmailSetting;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

class EmailService
{
    // ── Send using the tenant's own SMTP settings ──────────────────
    // Returns false if the tenant hasn't connected (or has an incomplete)
    // SMTP setup — caller can then fall back to the system mailer.
    // $attachments: array of ['content' => string, 'name' => string, 'mime' => string]
    // $cc: array of ['email' => string, 'name' => string|null]
    public static function send(int $tenantId, string $toEmail, string $toName, string $subject, string $html, array $attachments = [], array $cc = []): bool
    {
        $settings = EmailSetting::where('tenant_id', $tenantId)
            ->where('is_connected', true)
            ->first();

        if (!$settings || !$settings->smtp_host) {
            return false;
        }

        try {
            static::dispatch($settings, $toEmail, $toName, $subject, $html, $attachments, $cc);
            return true;
        } catch (\Exception $e) {
            Log::warning("Tenant email send failed: {$e->getMessage()}");
            return false;
        }
    }

    public static function test(EmailSetting $settings, string $toEmail): array
    {
        try {
            static::dispatch(
                $settings,
                $toEmail,
                'Test',
                'Test Email from your CRM',
                '<p style="font-family:Arial,sans-serif">This is a test email from your CRM. If you can see this, your email is connected correctly. ✅</p>'
            );
            return ['success' => true];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // Public + throwing variant — used where the caller wants to handle/log the failure itself.
    // $attachments: array of ['content' => string, 'name' => string, 'mime' => string]
    // $cc: array of ['email' => string, 'name' => string|null]
    public static function dispatch(EmailSetting $settings, string $toEmail, string $toName, string $subject, string $html, array $attachments = [], array $cc = []): void
    {
        $transport = Transport::fromDsn(static::dsn($settings));
        $mailer    = new Mailer($transport);

        $email = (new Email())
            ->from(new Address($settings->from_address, $settings->from_name ?? ''))
            ->to(new Address($toEmail, $toName))
            ->subject($subject)
            ->html($html);

        foreach ($cc as $ccRecipient) {
            $email->addCc(new Address($ccRecipient['email'], $ccRecipient['name'] ?? ''));
        }

        foreach ($attachments as $attachment) {
            $email->attach($attachment['content'], $attachment['name'] ?? null, $attachment['mime'] ?? null);
        }

        $mailer->send($email);
    }

    private static function dsn(EmailSetting $settings): string
    {
        $scheme = $settings->smtp_encryption === 'ssl' ? 'smtps' : 'smtp';
        $user   = rawurlencode((string) $settings->smtp_username);
        $pass   = rawurlencode((string) $settings->smtp_password);

        return "{$scheme}://{$user}:{$pass}@{$settings->smtp_host}:{$settings->smtp_port}";
    }
}
