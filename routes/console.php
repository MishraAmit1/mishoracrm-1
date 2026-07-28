<?php

use App\Jobs\SyncIndiaMartLeadsJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// ── IndiaMART lead sync — every 30 minutes ────────────────────────
Schedule::job(new SyncIndiaMartLeadsJob)->everyThirtyMinutes();

// ── Follow-up due/overdue reminders — every 5 minutes ─────────────
Schedule::command('followups:remind')->everyFiveMinutes();

// ── Invoice payment due/overdue reminders — daily ─────────────────
Schedule::command('invoices:remind-payments')->daily();
