<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\NotificationPreference;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    // ── Send notification to one user ─────────────────────────────
    public function send(
        string $type,
        User   $recipient,
        array  $data = [],
        ?User  $triggeredBy = null,
        ?string $url = null,
        mixed  $notifiable = null
    ): ?Notification {

        $typeConfig = config('notifications.types')[$type] ?? null;

        if (!$typeConfig) {
            Log::warning("Unknown notification type: {$type}");
            return null;
        }

        $tenantId = $recipient->tenant_id;
        $title    = $this->render($typeConfig['label'],   $data);
        $message  = $this->render($typeConfig['message'], $data);
        $channels = [];

        // ── In-app notification ───────────────────────────────────
        if (NotificationPreference::isEnabled($recipient->id, $tenantId, $type, 'in_app')) {
            $notification = Notification::create([
                'tenant_id'       => $tenantId,
                'user_id'         => $recipient->id,
                'triggered_by'    => $triggeredBy?->id,
                'type'            => $type,
                'title'           => $title,
                'message'         => $message,
                'url'             => $url,
                'icon'            => $typeConfig['icon'] ?? 'bell',
                'color'           => $typeConfig['color'] ?? 'accent',
                'notifiable_type' => $notifiable ? get_class($notifiable) : null,
                'notifiable_id'   => $notifiable?->id,
                'is_read'         => false,
                'channels_sent'   => [],
            ]);
            $channels[] = 'in_app';
        }

        // ── Email channel ─────────────────────────────────────────
        if (
            NotificationPreference::isEnabled($recipient->id, $tenantId, $type, 'email')
            && $recipient->email
            && config('notifications.channels.email.enabled')
        ) {
            $this->sendEmail($recipient, $title, $message, $url, $data);
            $channels[] = 'email';
        }

        // ── WhatsApp channel ──────────────────────────────────────
        if (
            NotificationPreference::isEnabled($recipient->id, $tenantId, $type, 'whatsapp')
            && $recipient->phone
            && config('notifications.channels.whatsapp.enabled')
        ) {
            $this->sendWhatsapp($recipient, $message);
            $channels[] = 'whatsapp';
        }

        // ── Slack channel (future) ────────────────────────────────
        if (
            NotificationPreference::isEnabled($recipient->id, $tenantId, $type, 'slack')
            && config('notifications.channels.slack.enabled')
        ) {
            $this->sendSlack($title, $message, $url, $data);
            $channels[] = 'slack';
        }

        // Update channels_sent on notification
        if (isset($notification)) {
            $notification->update(['channels_sent' => $channels]);
            return $notification;
        }

        return null;
    }

    // ── Send to multiple users ────────────────────────────────────
    public function sendToMany(
        string $type,
        array  $recipients,
        array  $data = [],
        ?User  $triggeredBy = null,
        ?string $url = null,
        mixed  $notifiable = null
    ): void {
        foreach ($recipients as $recipient) {
            $this->send($type, $recipient, $data, $triggeredBy, $url, $notifiable);
        }
    }

    // ── Send to all tenant staff ──────────────────────────────────
    public function sendToTenant(
        int    $tenantId,
        string $type,
        array  $data = [],
        ?User  $triggeredBy = null,
        ?string $url = null,
        mixed  $notifiable = null
    ): void {
        $users = User::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->get();

        foreach ($users as $user) {
            // Skip the one who triggered it
            if ($triggeredBy && $user->id === $triggeredBy->id) continue;

            $this->send($type, $user, $data, $triggeredBy, $url, $notifiable);
        }
    }

    // ── Render message variables ──────────────────────────────────
    private function render(string $template, array $data): string
    {
        foreach ($data as $key => $value) {
            $template = str_replace('{{' . $key . '}}', $value ?? '', $template);
        }
        return $template;
    }

    // ── Email channel handler ─────────────────────────────────────
    private function sendEmail(User $user, string $title, string $message, ?string $url, array $data): void
    {
        try {
            \Illuminate\Support\Facades\Mail::send([], [], function ($mail) use ($user, $title, $message, $url) {
                $mail->to($user->email, $user->name)
                     ->subject($title)
                     ->html($this->emailHtml($title, $message, $url));
            });
        } catch (\Exception $e) {
            Log::error("Notification email failed: " . $e->getMessage());
        }
    }

    // ── WhatsApp channel handler ──────────────────────────────────
    // When WhatsApp API integrated, replace this with API call
    private function sendWhatsapp(User $user, string $message): void
    {
        try {
            // Log for now — integrate WhatsApp API here
            \App\Models\WhatsappLog::create([
                'tenant_id'  => $user->tenant_id,
                'sent_by'    => null,
                'to_phone'   => $user->phone,
                'to_name'    => $user->name,
                'message'    => $message,
                'status'     => 'pending', // pending until API integrated
                'is_bulk'    => false,
            ]);
        } catch (\Exception $e) {
            Log::error("Notification WhatsApp failed: " . $e->getMessage());
        }
    }

    // ── Slack channel handler (future) ────────────────────────────
    private function sendSlack(string $title, string $message, ?string $url, array $data): void
    {
        // TODO: Integrate Slack Webhook
        // $webhookUrl = config('services.slack.webhook_url');
        // Http::post($webhookUrl, ['text' => "*{$title}*\n{$message}"]);
        Log::info("Slack notification (not integrated yet): {$title}");
    }

    // ── Email HTML template ───────────────────────────────────────
    private function emailHtml(string $title, string $message, ?string $url): string
    {
        $btn = $url
            ? "<a href='{$url}' style='display:inline-block;background:#6378ff;color:#fff;padding:10px 20px;border-radius:6px;text-decoration:none;font-size:13px;font-weight:600;margin-top:16px'>View Details →</a>"
            : '';

        return <<<HTML
<div style="max-width:500px;margin:0 auto;font-family:Arial,sans-serif;color:#1a1a2e">
  <div style="background:#6378ff;padding:20px 24px;border-radius:8px 8px 0 0">
    <h2 style="color:#fff;margin:0;font-size:18px">{$title}</h2>
  </div>
  <div style="background:#fff;padding:24px;border:1px solid #e2e8f0;border-top:none;border-radius:0 0 8px 8px">
    <p style="color:#4b5563;line-height:1.7;margin:0">{$message}</p>
    {$btn}
    <p style="color:#9ca3af;font-size:12px;margin-top:20px;border-top:1px solid #f1f5f9;padding-top:16px">
      This is an automated notification from your CRM system.
    </p>
  </div>
</div>
HTML;
    }

    // ── Static helper methods (shorthand) ─────────────────────────

    public static function notify(string $type, User $recipient, array $data = [], ?User $by = null, ?string $url = null, mixed $notifiable = null): ?Notification
    {
        return app(static::class)->send($type, $recipient, $data, $by, $url, $notifiable);
    }
}