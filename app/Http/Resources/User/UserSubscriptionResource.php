<?php

namespace App\Http\Resources\User;

use App\Models\Package;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserSubscriptionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $package = Package::where('stripe_id', $this->stripe_price)->first();

        return $package->toArray();
    }
}
