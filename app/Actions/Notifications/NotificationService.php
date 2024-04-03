<?php

namespace App\Actions\Services\Notifications;

use App\Actions\Services\Notifications\Channels\EmailNotificationChannel;
use App\Actions\Services\Notifications\Channels\FirebaseNotificationChannel;
use App\Actions\Services\Notifications\Channels\SendChampNotificationChannel;
use App\Actions\Services\Notifications\Channels\TermiiNotificationChannel;
use App\Enum\NotificationTypeEnum as EnumNotificationTypeEnum;
use App\Enums\Core\Notification\NotificationTypeEnum;
use App\Enums\Core\Notification\SmsProviderEnum;
use App\Models\School;
use App\Models\User;
use App\Repositories\Core\NotificationRepository;
use App\Repositories\Core\TokenRepository;
use Carbon\CarbonImmutable;

class NotificationService
{
    protected $channel;

    protected $title;

    protected $body;

    protected $image;

    protected $data;

    protected $view;

    protected $subject;

    protected $sms_provider;

    protected $attachments;

    protected $type;

    protected $url;

    protected $owner;

    /**
     * @var User|School|null
     */
    public function __construct(protected User|null $notifiable = null)
    {
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function setBody(string $body): self
    {
        $this->body = $body;

        return $this;
    }

    public function setImage(string $image): self
    {
        $this->image = $image;

        return $this;
    }

    public function setData(array $data): self
    {
        $this->data = $data;

        return $this;
    }

    public function setView(string $view): self
    {
        $this->view = $view;

        return $this;
    }

    public function setSubject(string $subject): self
    {
        $this->subject = $subject;

        return $this;
    }

    public function setAttachments(array $attachments): self
    {
        $this->attachments = $attachments;

        return $this;
    }

    public function setType(EnumNotificationTypeEnum $type): self
    {
        $this->type = $type->value;

        return $this;
    }

    public function setUrl(string $url): self
    {
        $this->url = $url;

        return $this;
    }

    public function sendEmail($email = null)
    {
        $channel = new EmailNotificationChannel(
            [
                'title' => $this->title,
                'body' => $this->body,
                'data' => $this->data,
                'view' => $this->view,
                'subject' => $this->subject,
                'attachments' => $this->attachments,
                'url' => $this->url,
                'type' => $this->type,
            ]
        );

        if (!$email && !$this->notifiable) {
            return false;
        }

        $email = $email ? $email : $this->notifiable->email;

        $channel->send($email);

        $notifiable = new NotificationRepository();

        $notifiable->create([
            'user_id' => $this->notifiable?->id ?? null,
            'channel' => 'email',
            'show' => false,
            'type' => $this->type,
            'title' => $this->title,
            'body' => $this->body,
            'data' => $this->data,
            'view' => $this->view,
            'subject' => $this->subject,
            'attachments' => $this->attachments,
            'url' => $this->url,
        ]);

        return $this;
    }

    public function sendPushNotification()
    {
        $data = $this->data;

        if (isset($this->url)) {
            $data['url'] = $this->url;
        }

        if (isset($this->type)) {
            $data['type'] = $this->type;
        }

        $channel = new FirebaseNotificationChannel(
            [
                'title' => $this->title,
                'body' => $this->body,
                'data' => $data,
                'view' => $this->view,
                'subject' => $this->subject,
            ]
        );

        $channel->send($this->notifiable);

        $notifiable = new NotificationRepository();

        $notifiable->create([
            'owner_id' => $this->notifiable?->id ?? null,
            'owner' => $this->owner ?? null,
            'channel' => 'push_notification',
            'show' => false,
            'title' => $this->title,
            'body' => $this->body,
            'data' => $data,
            'view' => $this->view,
            'subject' => $this->subject,
            'attachments' => $this->attachments,
            'url' => $this->url,
            'type' => $this->type,
        ]);

        return $this;
    }

    public function sendOtp($email = null, $title = 'Use the token to verify your account')
    {
        $token_data = [
            'purpose' => 'Email OTP to ' . $this->notifiable->email . ' @ ' . now(),
            'recipient' => $this->notifiable->email,
            'ttl' => 5,
            'user_id' => $this->notifiable->id
        ];

        $token_repo = new TokenRepository();
        $token = $token_repo->create($token_data);

        $notification = new $this;

        $notification->setSubject($title)
            ->setView('emails.core.send_token')
            ->setData([
                'user' => $this->notifiable,
                'token' => $token->token
            ])
            ->sendEmail();

        return [
            'status' => true,
            'message' => 'Token sent successfully',
            'data' => $token,
        ];
    }

    public function resendOtp($token_id)
    {
        $token_repo = new TokenRepository();

        $token = $token_repo->findById($token_id);

        $creation_time = CarbonImmutable::parse($token->created_at);
        $current_time = CarbonImmutable::now();

        $time_difference = $creation_time->diffInMinutes($current_time);

        if ($time_difference < 1) {
            return [
                'status' => false,
                'message' => 'token can only be resent after 1 minute',
                'data' => $token,
            ];
        }

        $token_repo->makeInvalid($token_id);

        return $this->sendOtp();
    }

    public function verifyOtp($token)
    {


        try {
            $token_repo = new TokenRepository();

            $token = $token_repo->findByToken($token);

            if (!$token) {
                return [
                    'status' => false,
                    'message' => 'Token not valid',
                    'data' => null,
                ];
            }

            $token_date = CarbonImmutable::parse($token->created_at)->addMinutes($token->ttl);

            $current_date_time = CarbonImmutable::now();



            if ($token_date->isPast()) {
                return [
                    'status' => false,
                    'message' => 'Token expired',
                    'data' => null,
                ];
            }

            if ($token->verified_at) {

                return [
                    'status' => false,
                    'message' => 'Token already used',
                    'data' => null,
                ];
            }

            if (!$token->valid) {

                return [
                    'status' => false,
                    'message' => 'Token not valid',
                    'data' => null,
                ];
            }

            $updated = $token_repo->update($token->id, [
                'verified_at' => $current_date_time->toDateTimeString(),
                'valid' => false,
            ]);

            if (!$updated) {
                // logError('SendChamp: Error verifying otp because model not updated', ['data' => $token->toArray(), 'issue' => 'model could not be updated']);

                return [
                    'status' => false,
                    'message' => 'We could not verify the token',
                    'data' => null,
                ];
            }

            return [
                'status' => true,
                'message' => 'Token verified',
                'data' => [
                    'verified_at' => $token_repo->findById($token->id)->verified_at
                ],
            ];
        } catch (\Throwable $th) {
            // logError('SendChamp: Error verifying otp ' . $th->getMessage(), ['data' => $token->toArray(), 'issue' => $th]);

            return [
                'status' => false,
                'message' => 'We could not verify the token',
                'data' => null,
            ];
        }
    }

    public function sendInAppNotification()
    {
        $notifiable = new NotificationRepository();

        $created = $notifiable->create([
            'user_id' => $this->notifiable?->id ?? null,
            'channel' => 'in_app',
            'show' => true,
            'title' => $this->title,
            'body' => $this->body,
            'data' => $this->data,
            'view' => $this->view,
            'subject' => $this->subject,
            'attachments' => $this->attachments,
            'url' => $this->url,
            'type' => $this->type,
        ]);

        if (!$created) {
            return [
                'status' => false,
                'message' => 'Error creating notification',
            ];
        }

        return [
            'status' => true,
            'message' => 'Notification created successfully',
        ];
    }
}
