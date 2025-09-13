<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CompanyController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Company API Routes
Route::prefix('companies')->group(function () {
    // Get all companies (with pagination and filters)
    Route::get('/', [CompanyController::class, 'index']);
    
    // Get multiple companies by symbols
    Route::post('/symbols', [CompanyController::class, 'getBySymbols']);
    
    // Get companies with financial data only
    Route::get('/with-financial-data', [CompanyController::class, 'withFinancialData']);
    
    // Get companies with earnings data only
    Route::get('/with-earnings', [CompanyController::class, 'withEarnings']);
    
    // Get top performers
    Route::get('/top-performers', [CompanyController::class, 'topPerformers']);
    
    // Get market summary
    Route::get('/market-summary', [CompanyController::class, 'marketSummary']);
    
    // Get earnings anticipation trading opportunities
    Route::get('/earnings-strategy', [CompanyController::class, 'earningsStrategy']);
    
    // Get earnings opportunities by confidence rating
    Route::get('/earnings-strategy/rating', [CompanyController::class, 'earningsStrategyByRating']);
    
    // Get single company by symbol (must be last to avoid conflicts)
    Route::get('/{symbol}', [CompanyController::class, 'show']);
});
