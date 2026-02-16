<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Run scoring rule optimization weekly (Sunday at 2 AM)
Schedule::command('scoring:optimize')->weeklyOn(0, '02:00');
