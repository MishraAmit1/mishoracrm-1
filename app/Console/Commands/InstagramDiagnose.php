<?php

namespace App\Console\Commands;

use App\Models\ErrorLog;
use App\Models\InstagramChatbotFlow;
use App\Models\InstagramLog;
use App\Models\InstagramSetting;
use App\Models\PlatformSetting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class InstagramDiagnose extends Command
{
    protected $signature = 'ig:diag';

    protected $description = 'Diagnose the Instagram Login connection + webhook wiring for every tenant';

    public function handle(): int
    {
        $igId     = PlatformSetting::get('meta_ig_app_id')  ?: PlatformSetting::get('meta_app_id');
        $igSecret = PlatformSetting::get('meta_ig_app_secret') ?: PlatformSetting::get('meta_app_secret');
        $this->line('meta_ig_app_id      = ' . (PlatformSetting::get('meta_ig_app_id') ?: '(not set)'));
        $this->line('meta_app_id (fb)    = ' . (PlatformSetting::get('meta_app_id') ?: '(not set)'));
        $this->line('effective client_id = ' . $igId);
        $this->line('ig secret set?      = ' . (PlatformSetting::get('meta_ig_app_secret') ? 'yes' : (PlatformSetting::get('meta_app_secret') ? 'falling back to fb secret' : 'NO')));
        $this->newLine();

        $this->info('=== APP-LEVEL WEBHOOK SUBSCRIPTIONS (Meta dashboard config) ===');
        if ($igId && $igSecret) {
            $appToken = $igId . '|' . $igSecret;
            $subs = Http::get("https://graph.facebook.com/v23.0/{$igId}/subscriptions", [
                'access_token' => $appToken,
            ])->json();
            $this->line(json_encode($subs));
            foreach ($subs['data'] ?? [] as $row) {
                if (($row['object'] ?? '') === 'instagram') {
                    $fields = collect($row['fields'] ?? [])->map(fn ($f) => is_array($f) ? $f['name'] : $f)->implode(',');
                    $this->line("  instagram -> callback={$row['callback_url']} active=" . var_export($row['active'] ?? null, true) . " fields={$fields}");
                }
            }
        } else {
            $this->warn('  cannot check — app id/secret missing');
        }
        $this->newLine();

        $this->info('=== INSTAGRAM SETTINGS ===');
        foreach (InstagramSetting::all() as $s) {
            $this->line("tenant={$s->tenant_id}");
            $this->line("  instagram_account_id = {$s->instagram_account_id}");
            $this->line('  page_id (secondary)  = ' . var_export($s->page_id, true));
            $this->line('  is_connected         = ' . var_export($s->is_connected, true));
            $this->line('  token length         = ' . strlen((string) $s->access_token));
            $this->line('  token_expires_at     = ' . var_export((string) $s->token_expires_at, true));
            $this->line("  webhook_verify_token = {$s->webhook_verify_token}");

            if ($s->access_token) {
                $me = Http::get('https://graph.instagram.com/v23.0/me', [
                    'fields'       => 'user_id,id,username,account_type',
                    'access_token' => $s->access_token,
                ])->json();
                $this->line('  /me                  = ' . json_encode($me));

                $subs = Http::get('https://graph.instagram.com/v23.0/me/subscribed_apps', [
                    'access_token' => $s->access_token,
                ])->json();
                $this->line('  /me/subscribed_apps  = ' . json_encode($subs));
            }
            $this->newLine();
        }

        $this->info('=== CHATBOT FLOWS ===');
        foreach (InstagramChatbotFlow::all() as $f) {
            $this->line("tenant={$f->tenant_id} name='{$f->name}' active=" . var_export($f->is_active, true)
                . ' default=' . var_export($f->is_default, true)
                . " match={$f->keyword_match} kw=" . json_encode($f->trigger_keywords));
        }
        $this->newLine();

        $this->info('=== WEBHOOK HITS (ErrorLog, last 12) ===');
        $hits = ErrorLog::whereIn('exception_class', ['InstagramWebhook', 'InstagramWebhookReceived'])
            ->latest()->limit(12)->get();
        if ($hits->isEmpty()) {
            $this->warn('!! NONE — Meta has never delivered to /webhook/instagram.');
        }
        foreach ($hits as $e) {
            $this->line($e->created_at . '  ' . $e->message . '  ' . json_encode($e->request_data));
        }
        $this->newLine();

        $this->info('=== INSTAGRAM LOGS (last 12) ===');
        foreach (InstagramLog::latest()->limit(12)->get() as $l) {
            $this->line($l->created_at . " [{$l->event_type}/{$l->status}] in=" . $l->incoming_text
                . ' | out=' . $l->outgoing_text
                . ' | err=' . mb_substr((string) $l->error_message, 0, 400));
        }

        return self::SUCCESS;
    }
}
