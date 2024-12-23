<?php

// namespace App\Jobs\Core\Trip;

// use App\Actions\Notifications\NotificationService;
// use App\Actions\Payment\StripeService;
// use App\Enum\ChargeTypeEnum;
// use App\Enum\NotificationTypeEnum;
// use App\Enum\PaymentTypeEnum;
// use App\Enum\TransactionStatusEnum;
// use App\Enum\TripStatusEnum;
// use App\Enum\TripTransactionTypeEnum;
// use App\Models\Trip;
// use App\Models\User;
// use App\Repositories\Core\TripRepository;
// use Illuminate\Bus\Queueable;
// use Illuminate\Contracts\Queue\ShouldQueue;
// use Illuminate\Foundation\Bus\Dispatchable;
// use Illuminate\Queue\InteractsWithQueue;
// use Illuminate\Queue\SerializesModels;
// use Illuminate\Support\Carbon;

// class EndTripsJob implements ShouldQueue
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

//         $current_time = Carbon::now();
//         $tripRepository->query()
//             ->where('end_time', '<=', $current_time)
//             ->where('status', TripStatusEnum::STARTED->value)
//             ->chunk(100, function ($trips) {
//                 foreach ($trips as $trip) {
//                     // Check if the car is parked and charging
//                     if (isParkedAndCharging($trip->vehicle_id)) {
//                         if ($trip->added_extra_time) {
//                             // Process payment for the extra time
//                             $this->processPayment($trip);

//                             // Update the trip status and ended_at time
//                             $trip->status = TripStatusEnum::ENDED->value;

//                             $trip->save();
//                         } else {
//                             // Update the trip status and ended_at time
//                             $trip->status = TripStatusEnum::ENDED->value;
//                             $trip->save();
//                         }
//                     } else {
//                         // Start penalty time until they end the trip themselves
//                         $this->startPenaltyTime($trip);
//                     }

//                     $this->notifyUser($trip);
//                 }
//             });
//     }

//     /**
//      * Process payment for the extra time.
//      */
//     private function processPayment(Trip $trip): void
//     {
//         ProcessExtraTimePaymentJob::dispatch($trip);
//     }

//     /**
//      * Start penalty time for the trip.
//      */
//     private function startPenaltyTime(Trip $trip): void
//     {
//         $trip->status = TripStatusEnum::PENALTY->value;
//         $trip->penalty_started_at = now();
//         $trip->save();
//     }

//     private function notifyUser(Trip $trip)
//     {

//         $user = User::find($trip->user_id);

//         $notification = new NotificationService($user);

//         $notification
//             ->setBody("You trip has ended, check all necessary information about the trip on your trips page")
//             ->setTitle('Trip ended')
//             ->setUrl('http://google.com')
//             ->setType(NotificationTypeEnum::TRIP_ENDED)
//             ->sendPushNotification()
//             ->sendInAppNotification();
//     }
// }




namespace App\Jobs\Core\Trip;

use Illuminate\Support\Facades\Log; // Add this import
use App\Actions\Notifications\NotificationService;
use App\Actions\Payment\StripeService;
use App\Enum\ChargeTypeEnum;
use App\Enum\NotificationTypeEnum;
use App\Enum\PaymentTypeEnum;
use App\Enum\TransactionStatusEnum;
use App\Enum\TripStatusEnum;
use App\Enum\TripTransactionTypeEnum;
use App\Models\Trip;
use App\Models\User;
use App\Repositories\Core\TripRepository;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class EndTripsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        Log::info('EndTripsJob constructed');
    }

    /**
     * Execute the job.
     */
    public function handle(TripRepository $tripRepository): void
    {
        Log::info('EndTripsJob started processing trips');

        $current_time = Carbon::now();

        try {
            $tripRepository->query()
                ->where('end_time', '<=', $current_time)
                ->where('status', TripStatusEnum::STARTED->value)
                ->chunk(100, function ($trips) {
                    Log::info('Processing chunk of trips', ['trip_count' => $trips->count()]);

                    foreach ($trips as $trip) {
                        try {
                            Log::info('Processing trip', [
                                'trip_id' => $trip->id,
                                'vehicle_id' => $trip->vehicle_id
                            ]);

                            // Check if the car is parked and charging
                            if (isParkedAndCharging($trip->vehicle_id)) {
                                Log::info('Trip vehicle is parked and charging', ['trip_id' => $trip->id]);

                                if ($trip->added_extra_time) {
                                    // Process payment for the extra time
                                    Log::info('Processing extra time payment', ['trip_id' => $trip->id]);
                                    $this->processPayment($trip);
                                }

                                // Update the trip status and ended_at time
                                $trip->status = TripStatusEnum::ENDED->value;
                                $trip->ended_trip = true;
                                $trip->save();

                                Log::info('Trip ended successfully', ['trip_id' => $trip->id]);
                            } else {
                                // Start penalty time until they end the trip themselves
                                Log::warning('Trip vehicle not parked or charging', [
                                    'trip_id' => $trip->id,
                                    'vehicle_id' => $trip->vehicle_id
                                ]);

                                $this->startPenaltyTime($trip);
                            }

                            // Notify user
                            $this->notifyUser($trip);
                        } catch (\Exception $tripException) {
                            Log::error('Error processing individual trip', [
                                'trip_id' => $trip->id,
                                'error' => $tripException->getMessage()
                            ]);
                        }
                    }
                });
        } catch (\Exception $e) {
            Log::error('Error in EndTripsJob', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }

        Log::info('EndTripsJob completed processing trips');
    }

    /**
     * Process payment for the extra time.
     */
    private function processPayment(Trip $trip): void
    {
        try {
            Log::info('Dispatching ProcessExtraTimePaymentJob', ['trip_id' => $trip->id]);
            ProcessExtraTimePaymentJob::dispatch($trip);
        } catch (\Exception $e) {
            Log::error('Error dispatching ProcessExtraTimePaymentJob', [
                'trip_id' => $trip->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Start penalty time for the trip.
     */
    private function startPenaltyTime(Trip $trip): void
    {
        try {
            $trip->status = TripStatusEnum::PENALTY->value;
            $trip->penalty_started_at = now();
            $trip->save();

            Log::warning('Trip penalty time started', [
                'trip_id' => $trip->id,
                'penalty_started_at' => $trip->penalty_started_at
            ]);
        } catch (\Exception $e) {
            Log::error('Error starting penalty time', [
                'trip_id' => $trip->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    private function notifyUser(Trip $trip)
    {
        try {
            $user = User::find($trip->user_id);

            if (!$user) {
                Log::warning('User not found for trip', ['trip_id' => $trip->id, 'user_id' => $trip->user_id]);
                return;
            }

            $notification = new NotificationService($user);

            $notification
                ->setBody("Your trip has ended, check all necessary information about the trip on your trips page")
                ->setTitle('Trip ended')
                ->setUrl('http://google.com')
                ->setType(NotificationTypeEnum::TRIP_ENDED)
                ->sendPushNotification()
                ->sendInAppNotification();

            Log::info('Notifications sent for trip', [
                'trip_id' => $trip->id,
                'user_id' => $user->id
            ]);
        } catch (\Exception $e) {
            Log::error('Error sending trip end notifications', [
                'trip_id' => $trip->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}
