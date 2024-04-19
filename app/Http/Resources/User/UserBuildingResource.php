<?php

namespace App\Http\Resources\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserBuildingResource extends JsonResource
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
            'name' => $this->name, // The name of the building
            'code' => $this->code,
            'address' => $this->address,
            'opening_time' => $this->opening_time,
            'closing_time' => $this->closing_time,
            'status' => $this->status,
            'image' => $this->image,
            'vehicle_count' => $this->vehicles()->count(),
            'status' => $this->pivot->status
        ];
    }
}
