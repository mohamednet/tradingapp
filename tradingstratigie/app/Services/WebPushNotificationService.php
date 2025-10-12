<?php

namespace App\Services;

use App\Models\PushSubscription;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

class WebPushNotificationService
{
    private WebPush $webPush;

    public function __construct()
    {
        $auth = [
            'VAPID' => [
                'subject' => config('services.webpush.subject'),
                'publicKey' => config('services.webpush.public_key'),
                'privateKey' => config('services.webpush.private_key'),
            ],
        ];

        $this->webPush = new WebPush($auth);
    }

    /**
     * Send notification to all subscribers
     */
    public function sendToAll(array $notification): array
    {
        $subscriptions = PushSubscription::all();
        
        if ($subscriptions->isEmpty()) {
            Log::warning('No push subscriptions found');
            return [
                'success' => 0,
                'failed' => 0,
                'expired' => 0,
                'total' => 0
            ];
        }

        $results = [
            'success' => 0,
            'failed' => 0,
            'expired' => 0,
            'total' => $subscriptions->count()
        ];

        foreach ($subscriptions as $subscription) {
            try {
                $result = $this->sendToSubscription($subscription, $notification);
                
                if ($result === true) {
                    $results['success']++;
                } elseif ($result === 'expired') {
                    $results['expired']++;
                    // Delete expired subscription
                    $subscription->delete();
                } else {
                    $results['failed']++;
                }
            } catch (\Exception $e) {
                Log::error('Failed to send notification', [
                    'subscription_id' => $subscription->id,
                    'error' => $e->getMessage()
                ]);
                $results['failed']++;
            }
        }

        Log::info('Notification batch sent', $results);
        return $results;
    }

    /**
     * Send notification to a single subscription
     */
    public function sendToSubscription(PushSubscription $pushSubscription, array $notification): bool|string
    {
        try {
            $subscription = Subscription::create([
                'endpoint' => $pushSubscription->endpoint,
                'publicKey' => $pushSubscription->p256dh_key,
                'authToken' => $pushSubscription->auth_token,
            ]);

            $payload = json_encode($notification);

            $report = $this->webPush->sendOneNotification(
                $subscription,
                $payload,
                ['TTL' => 3600] // Time to live: 1 hour
            );

            if ($report->isSuccess()) {
                return true;
            }

            // Check if subscription is expired
            if ($report->isSubscriptionExpired()) {
                Log::info('Subscription expired', [
                    'endpoint' => substr($pushSubscription->endpoint, 0, 50) . '...'
                ]);
                return 'expired';
            }

            Log::warning('Notification failed', [
                'reason' => $report->getReason(),
                'endpoint' => substr($pushSubscription->endpoint, 0, 50) . '...'
            ]);

            return false;

        } catch (\Exception $e) {
            Log::error('Exception sending notification', [
                'error' => $e->getMessage(),
                'subscription_id' => $pushSubscription->id
            ]);
            return false;
        }
    }

    /**
     * Create notification payload for trading opportunity
     */
    public function createOpportunityNotification(array $opportunity): array
    {
        $confidenceRating = $opportunity['confidence_rating'] ?? 'Neutral';
        
        $confidenceEmoji = match($confidenceRating) {
            'Very Good' => '🟢',
            'Good' => '🔵',
            'Neutral' => '🟡',
            'Bad' => '🟠',
            'Very Bad' => '🔴',
            default => '⚪'
        };

        $symbol = $opportunity['symbol'] ?? 'Unknown';
        $daysUntil = $opportunity['days_until_earnings'] ?? 0;
        $score = $opportunity['confidence_score'] ?? 0;

        return [
            'title' => "{$confidenceEmoji} {$symbol} - {$confidenceRating}",
            'body' => "Earnings in {$daysUntil} days | Score: {$score}",
            'icon' => url('/images/icons/icon-192x192.png'),
            'badge' => url('/images/icons/icon-72x72.png'),
            'vibrate' => [200, 100, 200],
            'tag' => 'opportunity-' . $symbol,
            'requireInteraction' => false,
            'data' => [
                'url' => url('/dashboard/earnings-strategy'),
                'symbol' => $symbol,
                'confidence' => $confidenceRating,
                'score' => $score,
                'days' => $daysUntil
            ]
        ];
    }

    /**
     * Create test notification
     */
    public function createTestNotification(): array
    {
        return [
            'title' => '🎯 Test Notification',
            'body' => 'This is a test notification from Earnings Strategy',
            'icon' => url('/images/icons/icon-192x192.png'),
            'badge' => url('/images/icons/icon-72x72.png'),
            'vibrate' => [200, 100, 200],
            'tag' => 'test-notification',
            'data' => [
                'url' => url('/dashboard/earnings-strategy'),
                'type' => 'test'
            ]
        ];
    }

    /**
     * Get subscription count
     */
    public function getSubscriptionCount(): int
    {
        return PushSubscription::count();
    }
}
