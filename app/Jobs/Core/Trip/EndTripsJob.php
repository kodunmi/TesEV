<?php

namespace App\Jobs\Core\Trip;

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
        //
    }

    /**
     * Execute the job.
     */
    public function handle(TripRepository $tripRepository): void
    {

        $current_time = Carbon::now();
        $tripRepository->query()
            ->where('end_time', '<=', $current_time)
            ->where('status', TripStatusEnum::STARTED->value)
            ->chunk(100, function ($trips) {
                foreach ($trips as $trip) {
                    // Check if the car is parked and charging
                    if (isParkedAndCharging($trip->vehicle_id)) {
                        if ($trip->added_extra_time) {
                            // Process payment for the extra time
                            $this->processPayment($trip);

                            // Update the trip status and ended_at time
                            $trip->status = TripStatusEnum::ENDED->value;

                            $trip->save();
                        } else {
                            // Update the trip status and ended_at time
                            $trip->status = TripStatusEnum::ENDED->value;
                            $trip->save();
                        }
                    } else {
                        // Start penalty time until they end the trip themselves
                        $this->startPenaltyTime($trip);
                    }

                    $this->notifyUser($trip);
                }
            });
    }

    /**
     * Process payment for the extra time.
     */
    private function processPayment(Trip $trip): void
    {
        ProcessExtraTimePaymentJob::dispatch($trip);
    }

    /**
     * Start penalty time for the trip.
     */
    private function startPenaltyTime(Trip $trip): void
    {
        $trip->status = TripStatusEnum::PENALTY->value;
        $trip->penalty_started_at = now();
        $trip->save();
    }

    private function notifyUser(Trip $trip)
    {

        $user = User::find($trip->user_id);

        $notification = new NotificationService($user);

        $notification
            ->setBody("You trip has ended, check all necessary information about the trip on your trips page")
            ->setTitle('Trip ended')
            ->setUrl('http://google.com')
            ->setType(NotificationTypeEnum::TRIP_ENDED)
            ->sendPushNotification()
            ->sendInAppNotification();
    }
}
