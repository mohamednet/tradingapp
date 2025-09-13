<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule financial data scraping twice daily
Schedule::command('scrape:financial-data')
    ->twiceDaily(9, 17) // 9 AM and 5 PM
    ->withoutOverlapping()
    ->runInBackground();
