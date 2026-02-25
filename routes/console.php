<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule campaign sending check
Schedule::command('campaigns:process-scheduled')->everyMinute();

// Daily engagement scoring
Schedule::command('contacts:update-engagement')->dailyAt('02:00');

// Reset SMTP hourly counters
Schedule::command('smtp:reset-hourly')->hourly();

// Widget subscription lifecycle check (daily at 6 AM)
Schedule::command('widget:check-subscriptions')->dailyAt('06:00');

// Auto-close resolved tickets after reopen window (daily at 3 AM)
Schedule::command('tickets:auto-close')->dailyAt('03:00');
