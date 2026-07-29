<?php

namespace App\Services;

use App\Models\DeviceToken;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FirebaseNotification;

class PushService
{
    public static function send(User $user, string $title, string $message, ?string $url = null): void
    {
        $tokens = DeviceToken::where('user_id', $user->id)
            ->where('is_active', true)
            ->pluck('device_token')
            ->all();

        if (empty($tokens)) {
            return;
        }

        try {
            $cloudMessage = CloudMessage::new()
                ->withNotification(FirebaseNotification::create($title, $message))
                ->withData(array_filter(['url' => $url]));

            $report = app(Messaging::class)->sendMulticast($cloudMessage, $tokens);

            $deadTokens = array_merge($report->unknownTokens(), $report->invalidTokens());

            if (!empty($deadTokens)) {
                DeviceToken::whereIn('device_token', $deadTokens)->update(['is_active' => false]);
            }
        } catch (\Exception $e) {
            Log::warning("Push notification failed: {$e->getMessage()}");
        }
    }
}
