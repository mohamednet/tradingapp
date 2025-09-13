<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Http\Resources\CompanyResource;
use App\Http\Resources\CompanyCollection;
use App\Services\EarningsStrategyService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CompanyController extends Controller
{
    /**
     * Get all companies with their financial data and earnings
     */
    public function index(Request $request): JsonResponse
    {
        $query = Company::with(['financialData', 'earnings'])
            ->orderBy('symbol');

        // Filter by symbols if provided
        if ($request->has('symbols')) {
            $symbols = explode(',', $request->symbols);
            $query->whereIn('symbol', $symbols);
        }

        // Filter by favorites if requested
        if ($request->boolean('favorites_only')) {
            $query->where('favorite', true);
        }

        // Pagination
        $perPage = min($request->get('per_page', 50), 100); // Max 100 per page
        $companies = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => CompanyResource::collection($companies->items()),
            'meta' => [
                'current_page' => $companies->currentPage(),
                'per_page' => $companies->perPage(),
                'total' => $companies->total(),
                'last_page' => $companies->lastPage(),
                'from' => $companies->firstItem(),
                'to' => $companies->lastItem()
            ]
        ]);
    }

    /**
     * Get a single company by symbol
     */
    public function show(string $symbol): JsonResponse
    {
        $company = Company::with(['financialData', 'earnings'])
            ->where('symbol', strtoupper($symbol))
            ->first();

        if (!$company) {
            return response()->json([
                'success' => false,
                'message' => 'Company not found',
                'error' => "No company found with symbol: {$symbol}"
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new CompanyResource($company)
        ]);
    }

    /**
     * Get multiple companies by symbols
     */
    public function getBySymbols(Request $request): JsonResponse
    {
        $request->validate([
            'symbols' => 'required|string',
        ]);

        $symbols = array_map('strtoupper', explode(',', $request->symbols));
        
        $companies = Company::with(['financialData', 'earnings'])
            ->whereIn('symbol', $symbols)
            ->orderBy('symbol')
            ->get();

        $foundSymbols = $companies->pluck('symbol')->toArray();
        $notFound = array_diff($symbols, $foundSymbols);

        return response()->json([
            'success' => true,
            'data' => CompanyResource::collection($companies),
            'meta' => [
                'requested_symbols' => $symbols,
                'found_symbols' => $foundSymbols,
                'not_found_symbols' => $notFound,
                'total_requested' => count($symbols),
                'total_found' => $companies->count()
            ]
        ]);
    }

    /**
     * Get companies with financial data only
     */
    public function withFinancialData(): JsonResponse
    {
        $companies = Company::with('financialData')
            ->whereHas('financialData')
            ->orderBy('symbol')
            ->get();

        return response()->json([
            'success' => true,
            'data' => CompanyResource::collection($companies),
            'meta' => [
                'total_companies_with_financial_data' => $companies->count()
            ]
        ]);
    }

    /**
     * Get companies with earnings data only
     */
    public function withEarnings(): JsonResponse
    {
        $companies = Company::with(['financialData', 'earnings'])
            ->whereHas('earnings')
            ->orderBy('symbol')
            ->get();

        return response()->json([
            'success' => true,
            'data' => CompanyResource::collection($companies),
            'meta' => [
                'total_companies_with_earnings' => $companies->count()
            ]
        ]);
    }

    /**
     * Get top performers (by 1-week performance)
     */
    public function topPerformers(Request $request): JsonResponse
    {
        $limit = min($request->get('limit', 10), 50);
        $period = $request->get('period', '1week'); // 1week or 1month

        $performanceField = $period === '1month' 
            ? 'price_performance_1month' 
            : 'price_performance_1week';

        $companies = Company::with(['financialData', 'earnings'])
            ->whereHas('financialData', function($query) use ($performanceField) {
                $query->whereNotNull($performanceField);
            })
            ->join('company_financial_data', 'companies.id', '=', 'company_financial_data.company_id')
            ->orderBy("company_financial_data.{$performanceField}", 'desc')
            ->select('companies.*')
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'data' => CompanyResource::collection($companies),
            'meta' => [
                'period' => $period,
                'limit' => $limit,
                'total_found' => $companies->count()
            ]
        ]);
    }

    /**
     * Get market summary statistics
     */
    public function marketSummary(): JsonResponse
    {
        $totalCompanies = Company::count();
        $companiesWithFinancialData = Company::whereHas('financialData')->count();
        $companiesWithEarnings = Company::whereHas('earnings')->count();

        // Calculate average performance
        $avgWeeklyPerformance = Company::join('company_financial_data', 'companies.id', '=', 'company_financial_data.company_id')
            ->whereNotNull('company_financial_data.price_performance_1week')
            ->avg('company_financial_data.price_performance_1week');

        $avgMonthlyPerformance = Company::join('company_financial_data', 'companies.id', '=', 'company_financial_data.company_id')
            ->whereNotNull('company_financial_data.price_performance_1month')
            ->avg('company_financial_data.price_performance_1month');

        // Get total market cap
        $totalMarketCap = Company::join('company_financial_data', 'companies.id', '=', 'company_financial_data.company_id')
            ->whereNotNull('company_financial_data.market_cap')
            ->sum('company_financial_data.market_cap');

        return response()->json([
            'success' => true,
            'data' => [
                'total_companies' => $totalCompanies,
                'companies_with_financial_data' => $companiesWithFinancialData,
                'companies_with_earnings' => $companiesWithEarnings,
                'financial_data_coverage' => round(($companiesWithFinancialData / $totalCompanies) * 100, 2),
                'earnings_coverage' => round(($companiesWithEarnings / $totalCompanies) * 100, 2),
                'market_performance' => [
                    'average_1week_performance' => round($avgWeeklyPerformance, 2),
                    'average_1month_performance' => round($avgMonthlyPerformance, 2)
                ],
                'total_market_cap' => $totalMarketCap,
                'total_market_cap_formatted' => '$' . number_format($totalMarketCap / 1000000000000, 2) . 'T'
            ]
        ]);
    }

    /**
     * Get earnings anticipation trading opportunities
     */
    public function earningsStrategy(): JsonResponse
    {
        $strategyService = app(EarningsStrategyService::class);
        $opportunities = $strategyService->getEarningsOpportunities();

        return response()->json([
            'success' => true,
            'data' => $opportunities['opportunities'],
            'summary' => $opportunities['summary'],
            'strategy_info' => [
                'name' => 'Earnings Anticipation Trading',
                'description' => 'Buy 1-4 weeks before earnings, sell 1-3 days before announcement',
                'target_window' => '7-30 days before earnings',
                'optimal_timing' => '14-21 days before earnings',
                'generated_at' => $opportunities['generated_at']
            ]
        ]);
    }

    /**
     * Get earnings opportunities filtered by confidence rating
     */
    public function earningsStrategyByRating(Request $request): JsonResponse
    {
        $rating = $request->get('rating', 'Good'); // Default to Good or better
        $validRatings = ['Very Good', 'Good', 'Neutral', 'Bad', 'Very Bad'];
        
        if (!in_array($rating, $validRatings)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid rating. Must be one of: ' . implode(', ', $validRatings)
            ], 400);
        }

        $strategyService = app(EarningsStrategyService::class);
        $opportunities = $strategyService->getEarningsOpportunities();

        // Filter by rating
        $filteredOpportunities = array_filter($opportunities['opportunities'], function($opportunity) use ($rating) {
            return $opportunity['eligible'] && $opportunity['confidence_rating'] === $rating;
        });

        return response()->json([
            'success' => true,
            'data' => array_values($filteredOpportunities),
            'filter' => [
                'rating' => $rating,
                'count' => count($filteredOpportunities)
            ],
            'strategy_info' => [
                'name' => 'Earnings Anticipation Trading',
                'description' => 'Buy 1-4 weeks before earnings, sell 1-3 days before announcement',
                'generated_at' => $opportunities['generated_at']
            ]
        ]);
    }
}
