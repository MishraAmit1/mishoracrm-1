<?php
// Run on the SERVER:  php artisan tinker scratchpad/ig_diag.php
use App\Models\ErrorLog;
use App\Models\InstagramSetting;
use App\Models\InstagramLog;
use App\Models\InstagramChatbotFlow;
use Illuminate\Support\Facades\Http;

echo "=== INSTAGRAM SETTINGS ===\n";
foreach (InstagramSetting::all() as $s) {
    echo "tenant={$s->tenant_id}\n";
    echo "  instagram_account_id = {$s->instagram_account_id}\n";
    echo "  page_id (secondary)  = " . var_export($s->page_id, true) . "\n";
    echo "  is_connected         = " . var_export($s->is_connected, true) . "\n";
    echo "  token length         = " . strlen((string) $s->access_token) . "\n";
    echo "  token_expires_at     = " . var_export((string) $s->token_expires_at, true) . "\n";
    echo "  webhook_verify_token = {$s->webhook_verify_token}\n";

    // Live probe: who does this token belong to + is it subscribed?
    $me = Http::get('https://graph.instagram.com/v23.0/me', [
        'fields' => 'user_id,id,username,account_type',
        'access_token' => $s->access_token,
    ])->json();
    echo "  /me                  = " . json_encode($me) . "\n";

    $subs = Http::get('https://graph.instagram.com/v23.0/me/subscribed_apps', [
        'access_token' => $s->access_token,
    ])->json();
    echo "  /me/subscribed_apps  = " . json_encode($subs) . "\n\n";
}

echo "=== CHATBOT FLOWS ===\n";
foreach (InstagramChatbotFlow::all() as $f) {
    echo "tenant={$f->tenant_id} name='{$f->name}' active=" . var_export($f->is_active, true)
        . " default=" . var_export($f->is_default, true)
        . " match={$f->keyword_match} kw=" . json_encode($f->trigger_keywords) . "\n";
}

echo "\n=== WEBHOOK HITS (ErrorLog 'InstagramWebhookReceived', last 8) ===\n";
$hits = ErrorLog::where('exception_class', 'InstagramWebhookReceived')->latest()->limit(8)->get();
if ($hits->isEmpty()) {
    echo "!! NONE. Meta has never delivered to /webhook/instagram.\n";
    echo "   -> App Dashboard > Instagram > Webhooks: subscribe the 'messages' and 'comments' FIELDS (app level).\n";
    echo "   -> And the account must allow message access in the Instagram app.\n";
}
foreach ($hits as $e) {
    echo $e->created_at . "  " . json_encode($e->request_data) . "\n\n";
}

echo "=== INSTAGRAM LOGS (last 12) ===\n";
foreach (InstagramLog::latest()->limit(12)->get() as $l) {
    echo $l->created_at . " [{$l->event_type}/{$l->status}] in=" . $l->incoming_text
        . " | out=" . $l->outgoing_text . " | err=" . mb_substr((string) $l->error_message, 0, 400) . "\n";
}
