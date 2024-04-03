<?php

namespace App\Actions\Services\Notifications\Channels;

use App\Mail\SendMailNotification;
use Illuminate\Support\Facades\Mail;

class EmailNotificationChannel
{
    public function __construct(
        protected $payload
    ) {
    }

    public function send($email)
    {
        $notification = new SendMailNotification($this->payload);

        Mail::to($email)->send($notification);
    }
}
