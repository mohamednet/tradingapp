<?php

namespace App\Http\Controllers;

use App\Services\EarningsStrategyService;
use App\Services\WebPushNotificationService;
use App\Models\PushSubscription;
use Illuminate\Http\JsonResponse;

class TestNotificationController extends Controller
{
    /**
     * Get current good opportunities for notifications
     */
    public function getOpportunities(
        EarningsStrategyService $strategyService,
        WebPushNotificationService $notificationService
    ): JsonResponse {
        $result = $strategyService->getEarningsOpportunities();
        $opportunities = $result['opportunities'] ?? [];
        
        // Filter for Very Good and Good only
        $goodOpportunities = collect($opportunities)->filter(function ($opp) {
            return $opp['eligible'] && in_array($opp['confidence_rating'], ['Very Good', 'Good']);
        })->values();

        // Prepare notification payloads
        $notifications = $goodOpportunities->map(function ($opp) use ($notificationService) {
            return $notificationService->createOpportunityNotification($opp);
        });

        return response()->json([
            'count' => $goodOpportunities->count(),
            'opportunities' => $goodOpportunities,
            'notifications' => $notifications,
            'subscribers' => PushSubscription::count()
        ]);
    }

    /**
     * Send test notification to all subscribers
     */
    public function sendTestToAll(WebPushNotificationService $notificationService): JsonResponse
    {
        $notification = $notificationService->createTestNotification();
        $results = $notificationService->sendToAll($notification);

        return response()->json([
            'success' => true,
            'results' => $results,
            'message' => "Sent to {$results['success']} subscriber(s), {$results['failed']} failed, {$results['expired']} expired"
        ]);
    }

    /**
     * Send notifications for all good opportunities
     */
    public function sendOpportunityNotifications(
        EarningsStrategyService $strategyService,
        WebPushNotificationService $notificationService
    ): JsonResponse {
        $result = $strategyService->getEarningsOpportunities();
        $opportunities = $result['opportunities'] ?? [];
        
        // Filter for Very Good and Good only
        $goodOpportunities = collect($opportunities)->filter(function ($opp) {
            return $opp['eligible'] && in_array($opp['confidence_rating'], ['Very Good', 'Good']);
        })->values();

        if ($goodOpportunities->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No good opportunities found'
            ]);
        }

        $totalResults = [
            'success' => 0,
            'failed' => 0,
            'expired' => 0,
            'opportunities_sent' => 0
        ];

        foreach ($goodOpportunities as $opportunity) {
            $notification = $notificationService->createOpportunityNotification($opportunity);
            $results = $notificationService->sendToAll($notification);
            
            $totalResults['success'] += $results['success'];
            $totalResults['failed'] += $results['failed'];
            $totalResults['expired'] += $results['expired'];
            $totalResults['opportunities_sent']++;
        }

        return response()->json([
            'success' => true,
            'results' => $totalResults,
            'message' => "Sent {$totalResults['opportunities_sent']} opportunities to {$totalResults['success']} subscribers"
        ]);
    }
}
