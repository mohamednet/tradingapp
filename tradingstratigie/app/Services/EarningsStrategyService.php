<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Earning;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class EarningsStrategyService
{
    /**
     * Get earnings anticipation trading opportunities
     */
    public function getEarningsOpportunities(): array
    {
        $companies = Company::with(['financialData', 'earnings'])
            ->whereHas('financialData')
            ->get();

        $opportunities = [];
        $summary = [
            'total_evaluated' => $companies->count(),
            'eligible_count' => 0,
            'very_good_count' => 0,
            'good_count' => 0,
            'neutral_count' => 0,
            'bad_count' => 0,
            'very_bad_count' => 0,
            'not_eligible_count' => 0
        ];

        foreach ($companies as $company) {
            $analysis = $this->analyzeCompany($company);
            $opportunities[] = $analysis;
            
            // Update summary counts
            if ($analysis['eligible']) {
                $summary['eligible_count']++;
                $summary[strtolower(str_replace(' ', '_', $analysis['confidence_rating'])) . '_count']++;
            } else {
                $summary['not_eligible_count']++;
            }
        }

        // Sort by confidence score (highest first)
        usort($opportunities, function($a, $b) {
            if (!$a['eligible'] && !$b['eligible']) return 0;
            if (!$a['eligible']) return 1;
            if (!$b['eligible']) return -1;
            return $b['confidence_score'] <=> $a['confidence_score'];
        });

        return [
            'opportunities' => $opportunities,
            'summary' => $summary,
            'generated_at' => now()->toISOString()
        ];
    }

    /**
     * Analyze a single company for earnings trading opportunity
     */
    private function analyzeCompany(Company $company): array
    {
        $analysis = [
            'symbol' => $company->symbol,
            'name' => $company->name,
            'eligible' => false,
            'confidence_rating' => 'Not Eligible',
            'confidence_score' => 0,
            'days_until_earnings' => null,
            'earnings_date' => null,
            'supporting_factors' => [],
            'risk_warnings' => [],
            'recommended_position_size' => 'None',
            'stage_results' => []
        ];

        // Stage 1: Eligibility Filter
        $eligibility = $this->checkEligibility($company);
        $analysis['stage_results']['stage_1_eligibility'] = $eligibility;
        
        if (!$eligibility['eligible']) {
            $analysis['confidence_rating'] = 'Not Eligible';
            $analysis['risk_warnings'] = $eligibility['reasons'];
            return $analysis;
        }

        $analysis['eligible'] = true;
        $analysis['days_until_earnings'] = $eligibility['days_until_earnings'];
        $analysis['earnings_date'] = $eligibility['earnings_date'];

        // Stage 2: Momentum Assessment
        $momentum = $this->assessMomentum($company);
        $analysis['stage_results']['stage_2_momentum'] = $momentum;

        // Stage 3: Sentiment Analysis (placeholder - using price performance as proxy)
        $sentiment = $this->analyzeSentiment($company);
        $analysis['stage_results']['stage_3_sentiment'] = $sentiment;

        // Stage 4: Timing Optimization
        $timing = $this->optimizeTiming($eligibility['days_until_earnings']);
        $analysis['stage_results']['stage_4_timing'] = $timing;

        // Stage 5: Final Confidence Rating
        $confidence = $this->calculateConfidence($momentum, $sentiment, $timing, $eligibility);
        $analysis['confidence_score'] = $confidence['score'];
        $analysis['confidence_rating'] = $confidence['rating'];
        $analysis['supporting_factors'] = $confidence['supporting_factors'];

        // Stage 6: Risk Flags
        $risks = $this->identifyRisks($company, $momentum, $sentiment);
        $analysis['risk_warnings'] = $risks;
        $analysis['stage_results']['stage_6_risks'] = $risks;

        // Recommended position size
        $analysis['recommended_position_size'] = $this->getPositionSize($confidence['rating']);

        return $analysis;
    }

    /**
     * Stage 1: Check basic eligibility requirements
     */
    private function checkEligibility(Company $company): array
    {
        $reasons = [];
        $eligible = true;

        $financial = $company->financialData;
        if (!$financial) {
            return [
                'eligible' => false,
                'reasons' => ['No financial data available'],
                'days_until_earnings' => null,
                'earnings_date' => null
            ];
        }

        // Liquidity Check
        if ($financial->market_cap < 2000000000) { // $2B
            $reasons[] = 'Market cap below $2B (' . number_format($financial->market_cap / 1000000000, 2) . 'B)';
            $eligible = false;
        }

        if ($financial->average_daily_volume < 1000000) { // 1M shares
            $reasons[] = 'Daily volume below 1M shares (' . number_format($financial->average_daily_volume / 1000000, 2) . 'M)';
            $eligible = false;
        }

        // Timing Check - find next earnings date
        $nextEarning = $company->earnings()
            ->where('earnings_release_date', '>', now())
            ->orderBy('earnings_release_date')
            ->first();

        $daysUntilEarnings = null;
        $earningsDate = null;

        if ($nextEarning) {
            $daysUntilEarnings = now()->diffInDays($nextEarning->earnings_release_date);
            $earningsDate = $nextEarning->earnings_release_date->toDateString();
            
            if ($daysUntilEarnings < 7 || $daysUntilEarnings > 30) {
                $reasons[] = "Earnings timing outside 7-30 day window ({$daysUntilEarnings} days)";
                $eligible = false;
            }
        } else {
            $reasons[] = 'No upcoming earnings date available';
            $eligible = false;
        }

        return [
            'eligible' => $eligible,
            'reasons' => $reasons,
            'days_until_earnings' => $daysUntilEarnings,
            'earnings_date' => $earningsDate,
            'market_cap_billions' => $financial->market_cap / 1000000000,
            'daily_volume_millions' => $financial->average_daily_volume / 1000000
        ];
    }

    /**
     * Stage 2: Assess current momentum
     */
    private function assessMomentum(Company $company): array
    {
        $financial = $company->financialData;
        
        $momentum = [
            'price_momentum_1week' => $financial->price_performance_1week ?? 0,
            'price_momentum_1month' => $financial->price_performance_1month ?? 0,
            'volume_analysis' => 'Normal', // Placeholder - would need historical volume data
            'momentum_score' => 0
        ];

        // Calculate momentum score
        $score = 0;
        
        // Price momentum (40 points max)
        if ($momentum['price_momentum_1week'] > 5) $score += 20;
        elseif ($momentum['price_momentum_1week'] > 0) $score += 10;
        elseif ($momentum['price_momentum_1week'] > -5) $score += 5;
        
        if ($momentum['price_momentum_1month'] > 10) $score += 20;
        elseif ($momentum['price_momentum_1month'] > 0) $score += 10;
        elseif ($momentum['price_momentum_1month'] > -10) $score += 5;

        $momentum['momentum_score'] = $score;
        
        return $momentum;
    }

    /**
     * Stage 3: Analyze sentiment (using price performance as proxy)
     */
    private function analyzeSentiment(Company $company): array
    {
        $financial = $company->financialData;
        
        // Use price performance and analyst target as sentiment proxy
        $currentPrice = $financial->current_stock_price;
        $analystTarget = $financial->fair_value_estimate;
        $pricePerformance = $financial->price_performance_1month ?? 0;
        
        $sentiment = [
            'current_sentiment' => 'Neutral',
            'sentiment_score' => 50,
            'analyst_vs_current' => 0,
            'price_trend' => $pricePerformance
        ];

        // Calculate sentiment based on multiple factors
        $score = 50; // Start neutral
        
        // Analyst target vs current price
        if ($analystTarget && $currentPrice) {
            $targetUpside = (($analystTarget - $currentPrice) / $currentPrice) * 100;
            $sentiment['analyst_vs_current'] = $targetUpside;
            
            if ($targetUpside > 20) $score += 25;
            elseif ($targetUpside > 10) $score += 15;
            elseif ($targetUpside > 0) $score += 10;
            elseif ($targetUpside > -10) $score -= 10;
            else $score -= 25;
        }
        
        // Price trend
        if ($pricePerformance > 15) $score += 15;
        elseif ($pricePerformance > 5) $score += 10;
        elseif ($pricePerformance > 0) $score += 5;
        elseif ($pricePerformance > -10) $score -= 5;
        else $score -= 15;

        $sentiment['sentiment_score'] = max(0, min(100, $score));
        
        // Convert to label
        if ($sentiment['sentiment_score'] >= 80) $sentiment['current_sentiment'] = 'Very Good';
        elseif ($sentiment['sentiment_score'] >= 65) $sentiment['current_sentiment'] = 'Good';
        elseif ($sentiment['sentiment_score'] >= 35) $sentiment['current_sentiment'] = 'Neutral';
        elseif ($sentiment['sentiment_score'] >= 20) $sentiment['current_sentiment'] = 'Bad';
        else $sentiment['current_sentiment'] = 'Very Bad';
        
        return $sentiment;
    }

    /**
     * Stage 4: Optimize timing
     */
    private function optimizeTiming(int $daysUntilEarnings): array
    {
        $timing = [
            'days_until_earnings' => $daysUntilEarnings,
            'timing_window' => '',
            'timing_multiplier' => 1.0,
            'timing_score' => 0
        ];

        if ($daysUntilEarnings >= 14 && $daysUntilEarnings <= 21) {
            $timing['timing_window'] = 'Sweet Spot';
            $timing['timing_multiplier'] = 1.3;
            $timing['timing_score'] = 30;
        } elseif ($daysUntilEarnings >= 7 && $daysUntilEarnings < 14) {
            $timing['timing_window'] = 'Moderate';
            $timing['timing_multiplier'] = 1.1;
            $timing['timing_score'] = 20;
        } elseif ($daysUntilEarnings > 21 && $daysUntilEarnings <= 30) {
            $timing['timing_window'] = 'Early';
            $timing['timing_multiplier'] = 0.9;
            $timing['timing_score'] = 10;
        } else {
            $timing['timing_window'] = 'Outside Window';
            $timing['timing_multiplier'] = 0.7;
            $timing['timing_score'] = 0;
        }

        return $timing;
    }

    /**
     * Stage 5: Calculate final confidence rating
     */
    private function calculateConfidence(array $momentum, array $sentiment, array $timing, array $eligibility): array
    {
        $baseScore = 0;
        $supportingFactors = [];

        // Momentum contribution (40 points max)
        $baseScore += $momentum['momentum_score'];
        if ($momentum['price_momentum_1week'] > 0) {
            $supportingFactors[] = "Positive weekly momentum ({$momentum['price_momentum_1week']}%)";
        }

        // Sentiment contribution (30 points max)
        $sentimentPoints = ($sentiment['sentiment_score'] / 100) * 30;
        $baseScore += $sentimentPoints;
        $supportingFactors[] = "Sentiment: {$sentiment['current_sentiment']} ({$sentiment['sentiment_score']}/100)";

        // Timing contribution (30 points max)
        $baseScore += $timing['timing_score'];
        $supportingFactors[] = "Timing: {$timing['timing_window']} ({$timing['days_until_earnings']} days)";

        // Apply timing multiplier
        $finalScore = $baseScore * $timing['timing_multiplier'];

        // Add liquidity factors
        if ($eligibility['market_cap_billions'] > 10) {
            $supportingFactors[] = "Large cap stock ({$eligibility['market_cap_billions']}B market cap)";
            $finalScore += 5;
        }
        
        if ($eligibility['daily_volume_millions'] > 5) {
            $supportingFactors[] = "High liquidity ({$eligibility['daily_volume_millions']}M daily volume)";
            $finalScore += 5;
        }

        // Determine rating
        $rating = 'Very Bad';
        if ($finalScore >= 85) $rating = 'Very Good';
        elseif ($finalScore >= 70) $rating = 'Good';
        elseif ($finalScore >= 50) $rating = 'Neutral';
        elseif ($finalScore >= 30) $rating = 'Bad';

        return [
            'score' => round($finalScore, 1),
            'rating' => $rating,
            'supporting_factors' => $supportingFactors
        ];
    }

    /**
     * Stage 6: Identify risk flags
     */
    private function identifyRisks(Company $company, array $momentum, array $sentiment): array
    {
        $risks = [];

        // High volatility risk (using price performance as proxy)
        if (abs($momentum['price_momentum_1week']) > 15) {
            $risks[] = "High volatility: {$momentum['price_momentum_1week']}% weekly change";
        }

        // Negative momentum risk
        if ($momentum['price_momentum_1month'] < -20) {
            $risks[] = "Significant monthly decline: {$momentum['price_momentum_1month']}%";
        }

        // Poor sentiment risk
        if ($sentiment['sentiment_score'] < 30) {
            $risks[] = "Poor market sentiment: {$sentiment['current_sentiment']}";
        }

        // Analyst target risk
        if ($sentiment['analyst_vs_current'] < -15) {
            $risks[] = "Trading above analyst targets by " . abs(round($sentiment['analyst_vs_current'], 1)) . "%";
        }

        return $risks;
    }

    /**
     * Get recommended position size based on confidence
     */
    private function getPositionSize(string $confidenceRating): string
    {
        return match($confidenceRating) {
            'Very Good' => 'Large (3-5% of portfolio)',
            'Good' => 'Medium (2-3% of portfolio)',
            'Neutral' => 'Small (1-2% of portfolio)',
            'Bad' => 'Very Small (0.5-1% of portfolio)',
            'Very Bad' => 'Avoid',
            default => 'None'
        };
    }
}
