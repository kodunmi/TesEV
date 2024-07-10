<?php

namespace App\Http\Resources\Core;

use App\Http\Resources\User\UserResource;
use App\Models\Package;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {

        $data =  [
            'id' => $this->id,
            'user' => new UserResource($this->owner),
            'type' => $this->type,
            'stripe_id' => $this->stripe_id,
            'stripe_status' => $this->stripe_status,
            'stripe_price' => $this->stripe_price,
            'quantity' => $this->quantity,
            'trial_ends_at' => $this->trial_ends_at,
            'ends_at' => $this->ends_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];

        $package = Package::where('stripe_id', $this->stripe_price)->first();

        $data['package'] = new PackageResource($package);

        return $data;
    }
}
