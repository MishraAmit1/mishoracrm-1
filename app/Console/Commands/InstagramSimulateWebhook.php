<?php

namespace App\Console\Commands;

use App\Models\InstagramLog;
use App\Models\InstagramSetting;
use App\Models\PlatformSetting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Fires a realistic Instagram Login webhook payload at our own
 * /webhook/instagram endpoint (with a valid X-Hub-Signature-256) so the whole
 * chain can be tested locally — webhook receipt → signature → tenant match →
 * chatbot/automation → outbound Instagram API call — WITHOUT waiting on Meta's
 * real webhook delivery (which is gated behind app publication).
 *
 *   php artisan ig:simulate dm      --text="price" --sender=17841400000000000
 *   php artisan ig:simulate comment --text="price"
 */
class InstagramSimulateWebhook extends Command
{
    protected $signature = 'ig:simulate
        {type=dm : dm|comment}
        {--text=price : message / comment text}
        {--sender= : Instagram-scoped ID of the sender (a real one lets the reply actually send)}
        {--tenant= : tenant_id (defaults to the only connected tenant)}';

    protected $description = 'Simulate a real Instagram webhook event end-to-end against our own endpoint';

    public function handle(): int
    {
        $type = $this->argument('type');
        $text = (string) $this->option('text');

        $setting = $this->option('tenant')
            ? InstagramSetting::where('tenant_id', $this->option('tenant'))->first()
            : InstagramSetting::where('is_connected', true)->first();

        if (!$setting || !$setting->instagram_account_id) {
            $this->error('No connected Instagram tenant found. Connect an account first.');
            return self::FAILURE;
        }

        $igId   = $setting->instagram_account_id;
        $sender = $this->option('sender') ?: ('99999999999999' . random_int(10, 99));
        $now    = now();
        $since  = $now->copy()->subSecond();

        $payload = $type === 'comment'
            ? [
                'object' => 'instagram',
                'entry'  => [[
                    'id'      => $igId,
                    'time'    => $now->timestamp,
                    'changes' => [[
                        'field' => 'comments',
                        'value' => [
                            'from'      => ['id' => $sender, 'username' => 'sim_user'],
                            'media'     => ['id' => 'sim_media_' . Str::random(8), 'media_product_type' => 'FEED'],
                            'id'        => 'sim_comment_' . Str::random(10),
                            'text'      => $text,
                        ],
                    ]],
                ]],
            ]
            : [
                'object' => 'instagram',
                'entry'  => [[
                    'id'        => $igId,
                    'time'      => $now->timestamp,
                    'messaging' => [[
                        'sender'    => ['id' => $sender],
                        'recipient' => ['id' => $igId],
                        'timestamp' => $now->getTimestampMs(),
                        'message'   => ['mid' => 'sim_' . Str::random(16), 'text' => $text],
                    ]],
                ]],
            ];

        $body   = json_encode($payload);
        $secret = PlatformSetting::get('meta_ig_app_secret') ?: PlatformSetting::get('meta_app_secret');
        $sig    = $secret ? 'sha256=' . hash_hmac('sha256', $body, $secret) : null;

        $url = url('/webhook/instagram');
        $this->line("POST {$url}");
        $this->line("  tenant={$setting->tenant_id} ig_account={$igId} sender={$sender} type={$type} text=\"{$text}\"");
        $this->line('  signature=' . ($sig ? 'attached' : 'NONE (no app secret configured)'));

        $req = Http::withBody($body, 'application/json');
        if ($sig) {
            $req = $req->withHeaders(['X-Hub-Signature-256' => $sig]);
        }
        $res = $req->post($url);

        $this->newLine();
        $this->line('HTTP ' . $res->status() . '  body=' . $res->body());
        $this->newLine();

        $this->info('=== InstagramLog rows created by this simulation ===');
        $rows = InstagramLog::where('tenant_id', $setting->tenant_id)
            ->where('created_at', '>=', $since)
            ->orderBy('id')
            ->get();

        if ($rows->isEmpty()) {
            $this->warn('  (none) — event was received but no tenant/handler matched. Check Superadmin → Error Logs.');
        }
        foreach ($rows as $r) {
            $this->line("  [{$r->event_type}/{$r->status}] in={$r->incoming_text} | out={$r->outgoing_text} | err=" . mb_substr((string) $r->error_message, 0, 600));
        }

        return self::SUCCESS;
    }
}
