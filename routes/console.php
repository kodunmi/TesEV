<?php

use App\Jobs\Core\Trip\EndTripsJob;
use App\Jobs\Core\Trip\Notifications\AfterTripNotificationJob;
use App\Jobs\Core\Trip\Notifications\BeforeTripNotificationJob;
use App\Jobs\Core\Trip\Notifications\OnTripNotificationJob;
use App\Jobs\Core\Trip\StartTripsJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();


Schedule::job(new BeforeTripNotificationJob)->everyMinute();
Schedule::job(new StartTripsJob)->everyMinute();
Schedule::job(new OnTripNotificationJob)->everyMinute();
Schedule::job(new EndTripsJob)->everyMinute();
Schedule::job(new AfterTripNotificationJob)->everyMinute();
