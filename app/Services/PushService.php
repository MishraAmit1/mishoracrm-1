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
    /** Must match the channel id declared in the mobile app's AndroidManifest.xml meta-data. */
    private const ANDROID_NOTIFICATION_CHANNEL_ID = 'fcm_default_channel';

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
            $cloudMessage = CloudMessage::new()
                ->withNotification(FirebaseNotification::create($title, $message))
                ->withData(array_filter(['url' => $url]))
                ->withAndroidConfig(AndroidConfig::fromArray([
                    'priority' => 'high',
                    'notification' => [
                        'channel_id' => self::ANDROID_NOTIFICATION_CHANNEL_ID,
                        'sound' => 'default',
                    ],
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
