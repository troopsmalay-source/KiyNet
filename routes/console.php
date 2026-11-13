<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('network:outbox')->everyMinute()->withoutOverlapping();
Schedule::command('network:sample-queues')->everyFiveMinutes()->withoutOverlapping();
Schedule::command('network:reconcile-stale')->hourly();
Schedule::command('network:rollup-usage')->dailyAt('01:15');
