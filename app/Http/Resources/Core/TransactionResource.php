<?php

namespace App\Http\Resources\Core;

use App\Http\Resources\User\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            "id" => $this->id,
            "public_id" =>  $this->public_id,
            "amount" => centToDollar($this->amount),
            "total_amount" => centToDollar($this->total_amount),
            "user_id" => $this->user_id,
            "reference" => $this->reference,
            "narration" => $this->narration,
            "title" => $this->title,
            "status" => $this->status,
            "entry" => $this->entry,
            "type" => $this->type,
            "channel" => $this->channel,
            "tax_amount" => centToDollar($this->tax_amount),
            "tax_percentage" => $this->tax_percentage,
            "transaction_date" => $this->transaction_date,
            "created_at" => $this->created_at,
            "updated_at" => $this->updated_at,
            "user" => new UserResource($this->user),
        ];
    }
}
