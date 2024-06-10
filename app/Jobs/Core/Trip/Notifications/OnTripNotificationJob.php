<?php

namespace App\Jobs\Core\Trip\Notifications;

use App\Actions\Notifications\NotificationService;
use App\Enum\NotificationTypeEnum;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class OnTripNotificationJob implements ShouldQueue
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
        $intervals = [
            '30 minutes' => Carbon::now()->addMinutes(30),
            '20 minutes' => Carbon::now()->addMinutes(20),
            '10 minutes' => Carbon::now()->addMinutes(10),
            '5 minutes' => Carbon::now()->addMinutes(5),
        ];

        foreach ($intervals as $intervalName => $intervalTime) {
            Trip::where('end_time', $intervalTime)
                ->where('status', 'active')
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
            ->setBody("Your trip is ending in $intervalName. Please prepare to end your trip.")
            ->setTitle('Trip Ending Soon')
            ->setUrl('http://your-website-url.com/trips')
            ->setType(NotificationTypeEnum::TRIP_ENDING_SOON)
            ->sendPushNotification()
            ->sendInAppNotification();
    }
}
