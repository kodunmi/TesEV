<?php

namespace App\Http\Resources\Core;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'body' => $this->body,
            'preview' => $this->preview,
            'channel' => $this->channel,
            'url' => $this->url,
            'is_read' => $this->is_read,
            'show' => $this->show,
            'read_by' => $this->read_by,
            'read_at' => $this->read_at,
            'sent_by' => $this->sent_by,
            'type' => $this->type,
            'markup_body' => $this->markup_body,
            'meta' => $this->meta,
            'data' => $this->data,
            'attachments' => $this->attachments,
            'public_id' => $this->public_id,
            'created_at' => $this->created_at,
        ];
    }
}
