<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Keep the verification rollups fresh (rebuild the last 2 days each hour) so the
// anti-counterfeit dashboard reads small summary rows, not millions of raw logs.
Schedule::command('anti-counterfeit:rollup --days=2')->hourly();
