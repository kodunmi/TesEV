<?php

namespace App\Http\Resources\User;

use App\Http\Resources\ComplianceResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            "first_name" => $this->first_name,
            "last_name" => $this->last_name,
            "email" => $this->email,
            "phone" => $this->phone,
            "phone_code" => $this->phone_code,
            "photo" => $this->photo->url ?? null,
            "gender" => $this->gender,
            "status" => $this->status,
            "date_of_birth" => $this->date_of_birth,
            "wallet" => centToDollar($this->wallet),
            "subscription_balance" => centToDollar($this->subscription_balance),
            "email_verified_at" => $this->email_verified_at,
            "fcm_token" => $this->fcm_token,
            "created_at" => $this->created_at,
            "updated_at" => $this->updated_at,
            "deleted_at" => $this->deleted_at,
            "compliance" =>  ComplianceResource::collection($this->compliances),
        ];
    }
}
