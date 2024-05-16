<?php

namespace App\Http\Resources\Core;

use App\Http\Resources\User\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TripResource extends JsonResource
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

            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'started_at' => $this->started_at,
            'ended_at' => $this->ended_at,
            'public_id' => $this->public_id,
            'booking_id' => $this->booking_id,
            'tax_amount' => centToDollar($this->tax_amount),
            'tax_percentage' => $this->tax_percentage,
            'status' => $this->status,

            'user' => new UserResource($this->user),
            'vehicle' => new VehicleResource($this->vehicle),
            'parent_trip' => $this->parentTrip,
            'extra_times' => $this->extensions,
            'meta_data' => $this->tripMetaData,
            'reports' => $this->reports,
            'trip_transactions' => TripTransactionResource::collection($this->tripTransactions)
        ];
    }
}
