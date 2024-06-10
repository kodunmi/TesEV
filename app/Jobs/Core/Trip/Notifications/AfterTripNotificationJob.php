<?php

namespace App\Jobs\Core\Trip\Notifications;

use App\Enum\NotificationTypeEnum;
use App\Enum\TripStatusEnum;
use App\Models\Trip;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class AfterTripNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $current_time = Carbon::now();

        Trip::whereNotNull('penalty_started_at')
            ->whereNull('penalty_ended_at')
            ->where('status', TripStatusEnum::ENDED->value)
            ->chunk(100, function ($trips) use ($current_time) {
                foreach ($trips as $trip) {
                    $penalty_time = $current_time->diffInMinutes(Carbon::parse($trip->penalty_started_at));

                    $message = "You have been in penalty time for $penalty_time minutes. Please return the car and end your trip to stop the penalty.";
                    $title = 'Penalty Time Alert';

                    notifyUser($trip, $message, $title, NotificationTypeEnum::TRIP_PENALTY_TIME);
                }
            });
    }
}
