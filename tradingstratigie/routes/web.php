<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;

Route::get('/', function () {
    return redirect('/dashboard/earnings-strategy');
});

Route::get('/dashboard/earnings-strategy', [DashboardController::class, 'earningsStrategy']);
Route::get('/dashboard/earnings-data', [DashboardController::class, 'getEarningsData']);
