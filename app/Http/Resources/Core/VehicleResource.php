<?php

namespace App\Http\Resources\Core;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VehicleResource extends JsonResource
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
            'name' => $this->name,
            'color' => $this->color,
            'status' => $this->status,
            'price_per_hour' => $this->price_per_hour,
            'image' => $this->image,
            'building_id' => $this->building_id,
            'public_id' => $this->public_id,
        ];
    }
}
