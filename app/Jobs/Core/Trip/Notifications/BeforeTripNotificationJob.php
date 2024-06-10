<?php

namespace App\Jobs\Core\Trip\Notifications;

use App\Actions\Notifications\NotificationService;
use App\Enum\NotificationTypeEnum;
use App\Enum\TripStatusEnum;
use App\Models\Trip;
use App\Models\User;
use App\Repositories\Core\TripRepository;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class BeforeTripNotificationJob implements ShouldQueue
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
        $intervals = [
            '2 hours' => Carbon::now()->addHours(2),
            '1 hour' => Carbon::now()->addHour(),
            '30 minutes' => Carbon::now()->addMinutes(30),
            '15 minutes' => Carbon::now()->addMinutes(15),
            '5 minutes' => Carbon::now()->addMinutes(5),
            '1 minute' => Carbon::now()->addMinute()
        ];

        foreach ($intervals as $intervalName => $intervalTime) {
            $tripRepository->query()
                ->where('start_time', $intervalTime)
                ->where('status', TripStatusEnum::RESERVED->value)
                ->chunk(100, function ($trips) use ($intervalName) {
                    foreach ($trips as $trip) {
                        $this->notifyUser($trip, $intervalName);
                    }
                });
        }
    }

    private function notifyUser(Trip $trip, string $intervalName): void
    {
        $user = User::find($trip->user_id);

        $notification = new NotificationService($user);

        $notification
            ->setBody("Your trip is starting in $intervalName. Please prepare.")
            ->setTitle('Upcoming Trip Notification')
            ->setUrl('http://google.com')
            ->setType(NotificationTypeEnum::TRIP_STARTING_SOON)
            ->sendPushNotification()
            ->sendInAppNotification();
    }
}
