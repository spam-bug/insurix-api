<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Before agents start their day in Manila: queue policy reminders first, so the lead scores
// that follow count any that are already due.
Schedule::command('policies:queue-follow-ups')->dailyAt('05:00')->timezone('Asia/Manila');
Schedule::command('leads:rescore')->dailyAt('05:15')->timezone('Asia/Manila');
