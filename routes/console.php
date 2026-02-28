<?php

use App\Jobs\CreateEnrollmentBookings;
use App\Jobs\FlagNoShows;
use App\Jobs\SendBookingReminder;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(new SendBookingReminder)->hourly();
Schedule::job(new FlagNoShows)->dailyAt('02:00');
Schedule::job(new CreateEnrollmentBookings)->dailyAt('01:00');
