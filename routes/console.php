<?php

/**
 * Console command route definitions.
 * Registers scheduled and custom artisan commands.
 */

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('billing:process-recurring-subscriptions')
    ->dailyAt('00:00')
    ->withoutOverlapping();
