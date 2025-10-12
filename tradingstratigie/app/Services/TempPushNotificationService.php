<?php

namespace App\Services;

use App\Models\PushSubscription;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class TempPushNotificationService
{
    /**
     * Send notification to all subscribers
     */
    public function sendToAll(string $title, string $body, array $data = []): int
    {
        $subscriptions = PushSubscription::all();
        $sentCount = 0;

        if ($subscriptions->isEmpty()) {
            Log::warning('No push subscriptions found');
            return 0;
        }

        foreach ($subscriptions as $subscription) {
            try {
                $this->sendNotification($subscription, $title, $body, $data);
                $sentCount++;
            } catch (\Exception $e) {
                Log::error('Failed to send notification', [
                    'subscription_id' => $subscription->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        Log::info("Sent {$sentCount} notifications");
        return $sentCount;
    }

    /**
     * Send notification using Web Push API simulation
     * Note: This is a simplified version. For production, use minishlink/web-push
     */
    private function sendNotification(PushSubscription $subscription, string $title, string $body, array $data): void
    {
        // For now, we'll just log the notification
        // In production, you would use the web-push library to actually send it
        
        Log::info('Notification prepared', [
            'endpoint' => substr($subscription->endpoint, 0, 50) . '...',
            'title' => $title,
            'body' => $body,
            'data' => $data
        ]);

        // TODO: Implement actual web push using minishlink/web-push
        // For testing, notifications will appear via the browser's service worker
        // when triggered from the frontend
    }

    /**
     * Get notification payload for trading opportunity
     */
    public function getOpportunityPayload(array $opportunity): array
    {
        $confidenceEmoji = match($opportunity['confidence_level']) {
            'Very Good' => '🟢',
            'Good' => '🔵',
            'Neutral' => '🟡',
            'Bad' => '🟠',
            'Very Bad' => '🔴',
            default => '⚪'
        };

        return [
            'title' => "{$confidenceEmoji} {$opportunity['symbol']} - {$opportunity['confidence_level']}",
            'body' => "Earnings in {$opportunity['days_until_earnings']} days | Score: {$opportunity['confidence_score']}",
            'icon' => '/images/icons/icon-192x192.png',
            'badge' => '/images/icons/icon-72x72.png',
            'data' => [
                'url' => '/dashboard/earnings-strategy',
                'symbol' => $opportunity['symbol'],
                'confidence' => $opportunity['confidence_level']
            ]
        ];
    }
}
