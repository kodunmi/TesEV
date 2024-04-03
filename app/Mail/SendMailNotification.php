<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Attachment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SendMailNotification extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(protected $payload)
    {
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->payload['subject'],
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: $this->payload['view'],
            with: [
                'data' => $this->payload['data'],
                'body' => $this->payload['body'],
                'title' => $this->payload['title'],
                'subject' => $this->payload['subject'],
            ]

        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        if (isset($this->payload['attachments'])) {
            $attachments = [];
            foreach ($this->payload['attachments'] as $attachment) {
                $attachment = Attachment::fromStorageDisk(isset($attachment['disk']) ? $attachment['disk'] : 's3', isset($attachment['path']) ? $attachment['path'] : '')
                    ->as(isset($attachment['name']) ? $attachment['name'] : '')
                    ->withMime(isset($attachment['mime']) ? $attachment['mime'] : '');

                $attachments[] = $attachment;
            }

            return $attachments;
        }

        return [];
    }
}
