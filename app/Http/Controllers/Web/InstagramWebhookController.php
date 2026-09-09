<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ErrorLog;
use App\Models\InstagramAutomation;
use App\Models\InstagramChatbotFlow;
use App\Models\InstagramLog;
use App\Models\InstagramSetting;
use App\Services\InstagramService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class InstagramWebhookController extends Controller
{
    // ── GET: Meta webhook verification ───────────────────────────
    public function verify(Request $request)
    {
        $mode      = $request->query('hub_mode');
        $token     = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        if ($mode !== 'subscribe') {
            return response('Invalid mode', 403);
        }

        // Find the tenant whose verify token matches
        $setting = InstagramSetting::where('webhook_verify_token', $token)->first();

        if (!$setting) {
            return response('Token mismatch', 403);
        }

        return response($challenge, 200)->header('Content-Type', 'text/plain');
    }

    // ── POST: Incoming Meta events ────────────────────────────────
    public function handle(Request $request): Response
    {
        $payload = $request->all();

        // Diagnostic trail, visible under Superadmin → Error Logs — this is
        // the only way to tell "Meta never reached us" apart from "Meta
        // reached us but entry.id didn't match any tenant's page_id", since
        // both look identical (silence) from the CRM's own Instagram Logs.
        ErrorLog::create([
            'tenant_id'       => null,
            'exception_class' => 'InstagramWebhookReceived',
            'http_status'     => 200,
            'message'         => 'Instagram webhook payload received',
            'url'             => $request->fullUrl(),
            'method'          => $request->method(),
            'request_data'    => $payload,
            'ip_address'      => $request->ip(),
            'user_agent'      => mb_substr($request->userAgent() ?? '', 0, 255),
            'created_at'      => now(),
        ]);

        if (($payload['object'] ?? '') !== 'instagram') {
            return response('ok', 200);
        }

        foreach ($payload['entry'] ?? [] as $entry) {
            // Instagram Login exposes two IDs for the same account (user_id /
            // IGSID and the app-scoped id). Meta keys entry.id — and the
            // recipient.id inside DM events — on either shape depending on the
            // event, so gather every candidate and match a tenant on any of
            // them (we persist both in instagram_account_id + page_id).
            $candidates = array_filter(array_unique(array_merge(
                [$entry['id'] ?? null],
                array_map(fn ($m) => $m['recipient']['id'] ?? null, $entry['messaging'] ?? []),
            )));

            $setting = InstagramSetting::where(function ($q) use ($candidates) {
                $q->whereIn('instagram_account_id', $candidates)
                  ->orWhereIn('page_id', $candidates);
            })->first();

            if (!$setting) continue;

            // Handle messaging (DMs) — the standard Messenger-style shape
            foreach ($entry['messaging'] ?? [] as $messaging) {
                $this->handleDm($setting, $messaging);
            }

            // Handle changes: comments, and (defensively) DMs delivered under
            // the changes[] shape instead of messaging[].
            foreach ($entry['changes'] ?? [] as $change) {
                $field = $change['field'] ?? '';
                $value = $change['value'] ?? [];

                if ($field === 'comments') {
                    $this->handleComment($setting, $value);
                } elseif ($field === 'messages' && isset($value['sender']['id'], $value['message'])) {
                    $this->handleDm($setting, $value);
                }
            }
        }

        return response('EVENT_RECEIVED', 200);
    }

    private function handleDm(InstagramSetting $setting, array $messaging): void
    {
        $senderId   = $messaging['sender']['id'] ?? null;
        $messageText = $messaging['message']['text'] ?? null;

        if (!$senderId || !$messageText) return;
        if (!empty($messaging['message']['is_echo'])) return;   // our own outbound copy
        if ($senderId === $setting->instagram_account_id) return; // own messages
        if ($senderId === $setting->page_id) return;             // own messages (secondary id)

        // Log incoming DM
        $log = InstagramLog::create([
            'tenant_id'          => $setting->tenant_id,
            'event_type'         => 'dm_received',
            'instagram_user_id'  => $senderId,
            'incoming_text'      => $messageText,
            'raw_payload'        => $messaging,
            'status'             => 'success',
        ]);

        // Check automations first (DM keyword)
        $automations = InstagramAutomation::where('tenant_id', $setting->tenant_id)
            ->where('is_active', true)
            ->where('trigger_type', 'dm_keyword')
            ->get();

        foreach ($automations as $automation) {
            if (!$automation->matchesDm($messageText)) continue;

            $this->executeAutomation($setting, $automation, $senderId, null, $messageText, $log);
            return;
        }

        // Then check chatbot flows
        $flows = InstagramChatbotFlow::where('tenant_id', $setting->tenant_id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $matched = null;
        foreach ($flows as $flow) {
            if (!$flow->is_default && $flow->matches($messageText)) {
                $matched = $flow;
                break;
            }
        }
        if (!$matched) {
            $matched = $flows->firstWhere('is_default', true);
        }

        if (!$matched) return;

        $matched->incrementTriggered();
        $log->update(['chatbot_flow_id' => $matched->id, 'event_type' => 'chatbot_triggered']);

        try {
            $service = new InstagramService($setting);
            $result  = $service->sendDmDetailed($senderId, $matched->response_message);
            $log->update([
                'outgoing_text' => $matched->response_message,
                'status'        => $result['success'] ? 'success' : 'failed',
                'error_message' => $result['success'] ? null : json_encode($result['body']),
            ]);
        } catch (\Throwable $e) {
            $log->update(['status' => 'failed', 'error_message' => $e->getMessage()]);
        }
    }

    private function handleComment(InstagramSetting $setting, array $value): void
    {
        $commentId   = $value['id'] ?? null;
        $commentText = $value['text'] ?? null;
        $postId      = $value['media']['id'] ?? null;
        $fromId      = $value['from']['id'] ?? null;

        if (!$commentId || !$commentText) return;
        if ($fromId && in_array($fromId, [$setting->instagram_account_id, $setting->page_id], true)) return; // own comments

        $log = InstagramLog::create([
            'tenant_id'         => $setting->tenant_id,
            'event_type'        => 'comment',
            'instagram_user_id' => $fromId,
            'post_id'           => $postId,
            'comment_id'        => $commentId,
            'incoming_text'     => $commentText,
            'raw_payload'       => $value,
            'status'            => 'success',
        ]);

        // Find matching automation
        $automations = InstagramAutomation::where('tenant_id', $setting->tenant_id)
            ->where('is_active', true)
            ->whereIn('trigger_type', ['any_post_comment', 'specific_post_comment'])
            ->get();

        foreach ($automations as $automation) {
            if ($automation->trigger_type === 'specific_post_comment' && $automation->post_id !== $postId) {
                continue;
            }
            if (!$automation->matchesComment($commentText)) continue;

            $this->executeAutomation($setting, $automation, $fromId, $commentId, $commentText, $log);
            return; // only first matching automation
        }

        $log->update(['status' => 'skipped']);
    }

    private function executeAutomation(
        InstagramSetting $setting,
        InstagramAutomation $automation,
        string $userId,
        ?string $commentId,
        string $text,
        InstagramLog $log
    ): void {
        $automation->incrementTriggered();
        $log->update(['automation_id' => $automation->id, 'event_type' => 'automation_triggered']);

        try {
            $service = new InstagramService($setting);

            if ($automation->action_type === 'send_dm' && $automation->dm_message) {
                $result = $service->sendDmDetailed($userId, $automation->dm_message);
                $log->update([
                    'outgoing_text' => $automation->dm_message,
                    'status'        => $result['success'] ? 'success' : 'failed',
                    'error_message' => $result['success'] ? null : json_encode($result['body']),
                ]);
            }

            if ($automation->action_type === 'reply_comment' && $commentId && $automation->comment_reply) {
                $result = $service->replyToCommentDetailed($commentId, $automation->comment_reply);
                $log->update([
                    'outgoing_text' => $automation->comment_reply,
                    'status'        => $result['success'] ? 'success' : 'failed',
                    'error_message' => $result['success'] ? null : json_encode($result['body']),
                ]);
            }
        } catch (\Throwable $e) {
            $log->update(['status' => 'failed', 'error_message' => $e->getMessage()]);
            Log::error('Instagram automation execution failed', ['error' => $e->getMessage()]);
        }
    }
}
