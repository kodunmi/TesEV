<?php

namespace App\Actions\Services\Notifications\Channels;

use App\Notifications\PushNotification;
use Illuminate\Notifications\Notification;

class FirebaseNotificationChannel
{

    public function __construct(
        protected $payload
    ) {
    }

    public function send($notifiable)
    {
        // Implement Firebase notification logic here

        $notification = new PushNotification($this->payload);

        $notifiable->notify($notification);
    }
}
