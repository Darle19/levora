<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(\Illuminate\Foundation\Inspiring::quote());
})->purpose('Display an inspiring quote');

// Daily flight data refresh at 6:00 AM Tashkent time (UTC+5 = 01:00 UTC)
Schedule::command('flights:refresh --days=60')->dailyAt('01:00');
