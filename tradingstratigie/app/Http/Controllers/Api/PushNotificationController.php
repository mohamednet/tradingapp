<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PushSubscription;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class PushNotificationController extends Controller
{
    /**
     * Get VAPID public key
     */
    public function getVapidPublicKey(): JsonResponse
    {
        return response()->json([
            'publicKey' => config('services.webpush.public_key')
        ]);
    }

    /**
     * Subscribe to push notifications
     */
    public function subscribe(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'endpoint' => 'required|string',
            'keys' => 'required|array',
            'keys.p256dh' => 'required|string',
            'keys.auth' => 'required|string',
        ]);

        try {
            // Store or update subscription
            PushSubscription::updateOrCreate(
                ['endpoint' => $validated['endpoint']],
                [
                    'p256dh_key' => $validated['keys']['p256dh'],
                    'auth_token' => $validated['keys']['auth'],
                    'user_agent' => $request->userAgent(),
                    'ip_address' => $request->ip(),
                ]
            );

            Log::info('Push subscription created', [
                'endpoint' => substr($validated['endpoint'], 0, 50) . '...'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Subscription successful'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to create push subscription', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to subscribe'
            ], 500);
        }
    }

    /**
     * Unsubscribe from push notifications
     */
    public function unsubscribe(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'endpoint' => 'required|string',
        ]);

        try {
            PushSubscription::where('endpoint', $validated['endpoint'])->delete();

            Log::info('Push subscription deleted', [
                'endpoint' => substr($validated['endpoint'], 0, 50) . '...'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Unsubscribed successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to delete push subscription', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to unsubscribe'
            ], 500);
        }
    }

    /**
     * Send test notification
     */
    public function sendTestNotification(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'endpoint' => 'required|string',
        ]);

        try {
            $subscription = PushSubscription::where('endpoint', $validated['endpoint'])->first();

            if (!$subscription) {
                return response()->json([
                    'success' => false,
                    'message' => 'Subscription not found'
                ], 404);
            }

            // Send test notification
            $this->sendPushNotification($subscription, [
                'title' => 'Test Notification',
                'body' => 'This is a test notification from Earnings Strategy',
                'icon' => '/images/icons/icon-192x192.png',
                'data' => [
                    'url' => '/dashboard/earnings-strategy'
                ]
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Test notification sent'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send test notification', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send notification'
            ], 500);
        }
    }

    /**
     * Send push notification to a subscription
     */
    private function sendPushNotification(PushSubscription $subscription, array $payload): void
    {
        // This is a placeholder - you'll need to implement actual web push
        // using a package like laravel-notification-channels/webpush
        // or minishlink/web-push
        
        Log::info('Sending push notification', [
            'endpoint' => substr($subscription->endpoint, 0, 50) . '...',
            'payload' => $payload
        ]);
    }
}
