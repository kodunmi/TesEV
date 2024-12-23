<?php

// namespace App\Jobs\Core\Trip\Notifications;

// use App\Enum\NotificationTypeEnum;
// use App\Enum\TripStatusEnum;
// use App\Models\Trip;
// use Illuminate\Bus\Queueable;
// use Illuminate\Contracts\Queue\ShouldQueue;
// use Illuminate\Foundation\Bus\Dispatchable;
// use Illuminate\Queue\InteractsWithQueue;
// use Illuminate\Queue\SerializesModels;
// use Illuminate\Support\Carbon;

// class AfterTripNotificationJob implements ShouldQueue
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
//     public function handle(): void
//     {
//         $current_time = Carbon::now();

//         Trip::whereNotNull('penalty_started_at')
//             ->whereNull('penalty_ended_at')
//             ->where('status', TripStatusEnum::ENDED->value)
//             ->chunk(100, function ($trips) use ($current_time) {
//                 foreach ($trips as $trip) {
//                     $penalty_time = $current_time->diffInMinutes(Carbon::parse($trip->penalty_started_at));

//                     $message = "You have been in penalty time for $penalty_time minutes. Please return the car and end your trip to stop the penalty.";
//                     $title = 'Penalty Time Alert';

//                     notifyUser($trip, $message, $title, NotificationTypeEnum::TRIP_PENALTY_TIME);
//                 }
//             });
//     }
// }

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
use Illuminate\Support\Facades\Log;

class AfterTripNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        Log::info('AfterTripNotificationJob created');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $current_time = Carbon::now();

            Log::info('Starting penalty time notification process', [
                'current_time' => $current_time->toDateTimeString()
            ]);

            $query = Trip::whereNotNull('penalty_started_at')
                ->whereNull('penalty_ended_at')
                ->where('status', TripStatusEnum::ENDED->value);

            $total_trips_count = $query->count();

            Log::info('Total trips in penalty', [
                'total_trips' => $total_trips_count
            ]);

            $query->chunk(100, function ($trips) use ($current_time) {
                Log::info('Processing chunk of penalty trips', [
                    'chunk_size' => $trips->count()
                ]);

                foreach ($trips as $trip) {
                    try {
                        $penalty_time = $current_time->diffInMinutes(Carbon::parse($trip->penalty_started_at));

                        $message = "You have been in penalty time for $penalty_time minutes. Please return the car and end your trip to stop the penalty.";
                        $title = 'Penalty Time Alert';

                        Log::info('Sending penalty notification', [
                            'trip_id' => $trip->id,
                            'booking_id' => $trip->booking_id,
                            'penalty_time_minutes' => $penalty_time
                        ]);

                        notifyUser($trip, $message, $title, NotificationTypeEnum::TRIP_PENALTY_TIME);

                        Log::info('Penalty notification sent successfully', [
                            'trip_id' => $trip->id,
                            'booking_id' => $trip->booking_id
                        ]);
                    } catch (\Exception $tripException) {
                        Log::error('Failed to process individual trip for penalty notification', [
                            'trip_id' => $trip->id,
                            'error' => $tripException->getMessage(),
                            'trace' => $tripException->getTraceAsString()
                        ]);
                    }
                }
            });

            Log::info('Penalty time notification process completed');
        } catch (\Exception $e) {
            Log::error('Penalty time notification job failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
