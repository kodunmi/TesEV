<?php

// namespace App\Jobs\Core\Trip;

// use App\Actions\Notifications\NotificationService;
// use App\Enum\NotificationTypeEnum;
// use App\Enum\TripStatusEnum;
// use App\Models\Trip;
// use App\Models\User;
// use App\Repositories\Core\TripRepository;
// use Carbon\Carbon;
// use Illuminate\Bus\Queueable;
// use Illuminate\Contracts\Queue\ShouldQueue;
// use Illuminate\Foundation\Bus\Dispatchable;
// use Illuminate\Queue\InteractsWithQueue;
// use Illuminate\Queue\SerializesModels;

// class StartTripsJob implements ShouldQueue
// {
//     use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

//     /**
//      * Create a new job instance.
//      */
//     public function __construct()
//     {
//         //
//     }

//     /**
//      * Execute the job.
//      */
//     public function handle(TripRepository $tripRepository): void
//     {
//         // Retrieve all trips that should start now
//         $current_time = Carbon::now();
//         $tripRepository->query()
//             ->where('start_time', '<=', $current_time)
//             ->where('status', TripStatusEnum::RESERVED->value)
//             ->chunk(100, function ($trips) use ($current_time) {
//                 foreach ($trips as $trip) {
//                     // Start the trip
//                     $trip->status = TripStatusEnum::STARTED->value;
//                     $trip->started_at = $current_time;
//                     $trip->save();

//                     // Notify the user
//                     $this->notifyUser($trip);
//                 }
//             });
//     }


//     private function notifyUser(Trip $trip): void
//     {
//         $user = User::find($trip->user_id);

//         $notification = new NotificationService($user);

//         $notification
//             ->setBody("Your trip has started, check all necessary information about the trip on your trips page")
//             ->setTitle('Trip Started')
//             ->setUrl('http://google.com')
//             ->setType(NotificationTypeEnum::TRIP_STARTED)
//             ->sendPushNotification()
//             ->sendInAppNotification();
//     }
// }




namespace App\Jobs\Core\Trip;

use Illuminate\Support\Facades\Log;
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
        Log::info('StartTripsJob constructed');
    }

    /**
     * Execute the job.
     */
    public function handle(TripRepository $tripRepository): void
    {
        Log::info('StartTripsJob started processing trips');

        // Retrieve all trips that should start now
        $current_time = Carbon::now();

        try {
            $tripsToStart = $tripRepository->query()
                ->where('start_time', '<=', $current_time)
                ->where('status', TripStatusEnum::RESERVED->value);

            $tripCount = $tripsToStart->count();

            Log::info('Trips to be started', [
                'total_trips' => $tripCount,
                'timestamp' => $current_time->toDateTimeString()
            ]);

            $successfullyStartedTrips = 0;
            $failedTrips = 0;

            if ($tripCount > 0) {
                $tripsToStart->chunk(100, function ($trips) use ($current_time, &$successfullyStartedTrips, &$failedTrips) {
                    Log::info('Processing chunk of trips', ['trip_count' => $trips->count()]);

                    foreach ($trips as $trip) {
                        try {
                            // Start the trip
                            $trip->status = TripStatusEnum::STARTED->value;
                            $trip->started_trip = true;
                            $trip->started_at = $current_time;
                            $trip->save();

                            Log::info('Trip started', [
                                'trip_id' => $trip->id,
                                'user_id' => $trip->user_id,
                                'started_at' => $current_time->toDateTimeString()
                            ]);

                            // Notify the user
                            $this->notifyUser($trip);

                            $successfullyStartedTrips++;
                        } catch (\Exception $tripException) {
                            Log::error('Error processing individual trip', [
                                'trip_id' => $trip->id,
                                'error' => $tripException->getMessage()
                            ]);

                            $failedTrips++;
                        }
                    }
                });

                // Log the final count of started and failed trips
                Log::info('Trip start process completed', [
                    'total_trips' => $tripCount,
                    'successfully_started' => $successfullyStartedTrips,
                    'failed_trips' => $failedTrips
                ]);
            } else {
                Log::info('No trips to start at this time');
            }
        } catch (\Exception $e) {
            Log::error('Error in StartTripsJob', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }

        Log::info('StartTripsJob completed processing trips');
    }

    private function notifyUser(Trip $trip): void
    {
        try {
            $user = User::find($trip->user_id);

            if (!$user) {
                Log::warning('User not found for trip', [
                    'trip_id' => $trip->id,
                    'user_id' => $trip->user_id
                ]);
                return;
            }

            $notification = new NotificationService($user);

            $notification
                ->setBody("Your trip has started, check all necessary information about the trip on your trips page")
                ->setTitle('Trip Started')
                ->setUrl('http://google.com')
                ->setType(NotificationTypeEnum::TRIP_STARTED)
                ->sendPushNotification()
                ->sendInAppNotification();

            Log::info('Notifications sent for started trip', [
                'trip_id' => $trip->id,
                'user_id' => $user->id
            ]);
        } catch (\Exception $e) {
            Log::error('Error sending trip start notifications', [
                'trip_id' => $trip->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}
