<?php

namespace App\Services;

use App\Models\DeviceToken;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\AndroidConfig;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FirebaseNotification;

class PushService
{
    /**
     * @return array{status: string, error: ?string}
     */
    public static function send(User $user, string $title, string $message, ?string $url = null): array
    {
        $tokens = DeviceToken::where('user_id', $user->id)
            ->where('is_active', true)
            ->pluck('device_token')
            ->all();

        if (empty($tokens)) {
            return ['status' => 'skipped', 'error' => 'No active device token'];
        }

        try {
            // NOTE: do not set an explicit android channel_id here until the app build that
            // declares "fcm_default_channel" in AndroidManifest.xml has rolled out to users —
            // targeting a channel id that doesn't exist yet on-device makes Android silently
            // drop the notification instead of falling back to FCM's own managed default channel.
            $cloudMessage = CloudMessage::new()
                ->withNotification(FirebaseNotification::create($title, $message))
                ->withData(array_filter(['url' => $url]))
                ->withAndroidConfig(AndroidConfig::fromArray([
                    'priority' => 'high',
                ]));

            $report = app(Messaging::class)->sendMulticast($cloudMessage, $tokens);

            $deadTokens = array_merge($report->unknownTokens(), $report->invalidTokens());

            if (!empty($deadTokens)) {
                DeviceToken::whereIn('device_token', $deadTokens)->update(['is_active' => false]);
            }

            if ($report->successes()->count() === 0) {
                $error = $report->failures()->getItems()[0]?->error()?->getMessage() ?? 'All device tokens failed';
                return ['status' => 'failed', 'error' => $error];
            }

            return ['status' => 'sent', 'error' => null];
        } catch (\Exception $e) {
            Log::warning("Push notification failed: {$e->getMessage()}");
            return ['status' => 'failed', 'error' => $e->getMessage()];
        }
    }
}
