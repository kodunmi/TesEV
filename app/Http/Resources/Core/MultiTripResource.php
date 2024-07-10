<?php

namespace App\Http\Resources\Core;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MultiTripResource extends JsonResource
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
            "public_id" => $this->public_id,
            "booking_id" => $this->booking_id,

            "start_time" => $this->start_time,
            "end_time" => $this->end_time,

            'started_at' => $this->started_at,
            'ended_at' => $this->ended_at,

            "started_trip" => $this->started_trip,
            "ended_trip" => $this->ended_trip,

            'time_used' => calculateTimeUsed($this->start_time),
            'percentage_of_time_used' => calculatePercentageOfTimeUsed($this->start_time, $this->end_time),

            "user_id" => $this->user_id,
            "tax_amount" => centToDollar($this->tax_amount),
            "tax_percentage" => $this->tax_percentage,
            "amount" => centToDollar($this->tripTransactions()->oldest()->first()->amount ?? 0),
            "total_amount" => centToDollar($this->tripTransactions()->oldest()->first()->total_amount ?? 0),
            "status" => $this->status,
            "remove_belongings" => $this->remove_belongings,
            "remove_trash" => $this->remove_trash,
            "plug_vehicle" => $this->plug_vehicle,
            "created_at" => $this->created_at,
            "updated_at" => $this->updated_at,
            "duration" => calculateMinutesDifference($this->start_time, $this->end_time) / 60,
            "vehicle" => new VehicleResource($this->vehicle),
        ];
    }
}
