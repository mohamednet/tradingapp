<?php

namespace App\Http\Controllers;

use App\Services\EarningsStrategyService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Show the earnings strategy dashboard
     */
    public function earningsStrategy()
    {
        return view('dashboard.earnings-strategy');
    }

    /**
     * Get filtered earnings strategy data for AJAX requests
     */
    public function getEarningsData(Request $request)
    {
        $strategyService = app(EarningsStrategyService::class);
        $opportunities = $strategyService->getEarningsOpportunities();

        // Apply filters
        $filtered = $this->applyFilters($opportunities['opportunities'], $request);

        return response()->json([
            'success' => true,
            'data' => $filtered,
            'summary' => $opportunities['summary'],
            'filters_applied' => [
                'eligibility' => $request->get('eligibility'),
                'favorites' => $request->get('favorites'),
                'confidence_rating' => $request->get('confidence_rating'),
                'time_period' => $request->get('time_period')
            ]
        ]);
    }

    /**
     * Apply filters to opportunities data
     */
    private function applyFilters(array $opportunities, Request $request): array
    {
        $filtered = $opportunities;

        // Filter by eligibility
        if ($request->get('eligibility') === 'eligible_only') {
            $filtered = array_filter($filtered, fn($opp) => $opp['eligible']);
        }

        // Filter by favorites (placeholder - would need to implement favorites system)
        if ($request->get('favorites') === 'favorites_only') {
            // For now, just return all since we don't have favorites implemented
            // $filtered = array_filter($filtered, fn($opp) => $opp['is_favorite'] ?? false);
        }

        // Filter by confidence rating
        if ($request->get('confidence_rating') && $request->get('confidence_rating') !== 'all') {
            $rating = $request->get('confidence_rating');
            $filtered = array_filter($filtered, fn($opp) => $opp['confidence_rating'] === $rating);
        }

        // Filter by time period
        if ($request->get('time_period') && $request->get('time_period') !== 'all') {
            $period = $request->get('time_period');
            $filtered = array_filter($filtered, function($opp) use ($period) {
                if (!$opp['eligible'] || !$opp['days_until_earnings']) return false;
                
                $days = $opp['days_until_earnings'];
                return match($period) {
                    '1_week' => $days >= 7 && $days <= 14,
                    '2_weeks' => $days >= 14 && $days <= 21,
                    '3_weeks' => $days >= 21 && $days <= 28,
                    '1_month' => $days >= 7 && $days <= 30,
                    default => true
                };
            });
        }

        return array_values($filtered);
    }
}
