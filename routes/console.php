<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Run scoring rule optimization weekly (Sunday at 2 AM)
Schedule::command('scoring:optimize')->weeklyOn(0, '02:00');

// Demo mode: refresh demo data weekly (Monday at 3 AM) to keep demos looking fresh.
// Only runs when DEMO_MODE=true is set in environment (set by install.sh for demo deploys).
if (env('DEMO_MODE', false)) {
    Schedule::command('demo:refresh --weeks=2')
        ->weeklyOn(1, '03:00')
        ->withoutOverlapping()
        ->appendOutputTo(storage_path('logs/demo-refresh.log'));
}
