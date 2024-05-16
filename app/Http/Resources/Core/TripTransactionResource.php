<?php

namespace App\Http\Resources\Core;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TripTransactionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'trip_id' => $this->trip_id,
            'building_id' => $this->building_id,
            'vehicle_id' => $this->vehicle_id,
            'user_id' => $this->user_id,
            'reference' => $this->reference,
            'amount' => centToDollar($this->amount),
            'total_amount' => centToDollar($this->total_amount),
            'status' => $this->status,
            'public_id' => $this->public_id,
            'tax_amount' => centToDollar($this->tax_amount),
            'tax_percentage' => $this->tax_percentage,
            'transactions' => TransactionResource::collection($this->transactions)
        ];
    }
}
