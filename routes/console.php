<?php

declare(strict_types=1);

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

// After the reminders and scores are in: each member's morning count of follow-ups for today.
Schedule::command('follow-ups:notify-due')->dailyAt('07:00')->timezone('Asia/Manila');
Schedule::command('notifications:prune')->dailyAt('03:00')->timezone('Asia/Manila');

// Deleted workspaces past their grace period, with their files.
Schedule::command('workspaces:purge')->dailyAt('02:30')->timezone('Asia/Manila');

// Workspace exports past the days they're kept.
Schedule::command('exports:prune')->dailyAt('02:45')->timezone('Asia/Manila');
