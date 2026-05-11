<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('certs:check-expiry --days=60')
    ->dailyAt('08:00')
    ->timezone('America/Paramaribo')
    ->withoutOverlapping();

Schedule::command('contracts:check-expiry --days=60')
    ->dailyAt('08:05')
    ->timezone('America/Paramaribo')
    ->withoutOverlapping();

Schedule::command('resolutions:check-expiry --days=60')
    ->dailyAt('08:10')
    ->timezone('America/Paramaribo')
    ->withoutOverlapping();

Schedule::command('kabinet:check-deadlines --days=14')
    ->dailyAt('08:15')
    ->timezone('America/Paramaribo')
    ->withoutOverlapping();
