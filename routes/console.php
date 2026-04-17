<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(\Illuminate\Foundation\Inspiring::quote());
})->purpose('Display an inspiring quote');

// Daily flight data refresh at 6:00 AM Tashkent time (UTC+5 = 01:00 UTC).
// Output is captured in a dedicated log so scheduled runs are diagnosable
// even if the OS-cron crontab pipes everything to /dev/null.
Schedule::command('flights:refresh --days=60')
    ->dailyAt('01:00')
    ->appendOutputTo(storage_path('logs/flights-refresh.log'))
    ->onFailure(function () {
        Log::error('flights:refresh scheduled task failed (see flights-refresh.log)');
    });
