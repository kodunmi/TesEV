<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ComplianceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'driver_license_front' => $this->driverLicenseFront,
            'driver_license_back' => $this->driverLicenseBack,
            'photo' => $this->photo,
            'license_state' => $this->license_state,
            'poster_code' => $this->poster_code,
            'license_number' => $this->license_number,
            'expiration_date' => $this->expiration_date,
            'active' => $this->active,
            'license_verified' => $this->license_verified,
            'license_verified_at' => $this->license_verified_at,
        ];
    }
}
