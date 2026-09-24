<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Cerrar tickets inactivos diariamente a las 6:00 AM
Schedule::command('tickets:cerrar-inactivos --dias=3')->dailyAt('06:00');
