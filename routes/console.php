<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ── Property module scheduled jobs ────────────────────────────────────────────
Schedule::command('property:mark-overdue')
    ->dailyAt('00:05')
    ->withoutOverlapping()
    ->runInBackground();

