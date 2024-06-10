<?php

namespace App\Jobs\Core\Trip;

use App\Actions\Notifications\NotificationService;
use App\Enum\NotificationTypeEnum;
use App\Enum\TripStatusEnum;
use App\Models\Trip;
use App\Models\User;
use App\Repositories\Core\TripRepository;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class StartTripsJob implements ShouldQueue
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
    public function handle(TripRepository $tripRepository): void
    {
        // Retrieve all trips that should start now
        $current_time = Carbon::now();
        $tripRepository->query()
            ->where('start_time', '<=', $current_time)
            ->where('status', TripStatusEnum::RESERVED->value)
            ->chunk(100, function ($trips) use ($current_time) {
                foreach ($trips as $trip) {
                    // Start the trip
                    $trip->status = 'active';
                    $trip->started_at = $current_time;
                    $trip->save();

                    // Notify the user
                    $this->notifyUser($trip);
                }
            });
    }


    private function notifyUser(Trip $trip): void
    {
        $user = User::find($trip->user_id);

        $notification = new NotificationService($user);

        $notification
            ->setBody("Your trip has started, check all necessary information about the trip on your trips page")
            ->setTitle('Trip Started')
            ->setUrl('http://google.com')
            ->setType(NotificationTypeEnum::TRIP_STARTED)
            ->sendPushNotification()
            ->sendInAppNotification();
    }
}
