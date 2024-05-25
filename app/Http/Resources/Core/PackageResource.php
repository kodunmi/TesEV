<?php

namespace App\Http\Resources\Core;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PackageResource extends JsonResource
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
            'description' => $this->description,
            'amount' => centToDollar($this->amount),
            'hours' => $this->hours,
            'frequency' => $this->frequency,
            'active' => $this->active,
            'public_id' => $this->public_id,
        ];
    }
}
