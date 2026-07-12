<?php

use App\Models\UserActivity;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('communications:send-emails')->everyMinute();

// Keeps the audit trail from growing forever across every school — see
// UserActivity::RETENTION_DAYS for how long a row is kept.
Schedule::command('model:prune', ['--model' => UserActivity::class])->daily();
