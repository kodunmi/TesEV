<?php

namespace App\Repositories\User;

use App\Interfaces\User\ComplianceRepositoryInterface;
use App\Models\Compliance;

class ComplianceRepository implements ComplianceRepositoryInterface
{

    public function create(array $data): Compliance
    {
        $compliance = new Compliance();

        $compliance->driver_license_front = $data['driver_license_front'] ?? null;
        $compliance->driver_license_back = $data['driver_license_back'] ?? null;
        $compliance->photo = $data['photo'] ?? null;
        $compliance->license_state = $data['license_state'] ?? null;
        $compliance->poster_code = $data['poster_code'] ?? null;
        $compliance->license_number = $data['license_number'] ?? null;
        $compliance->expiration_date = $data['expiration_date'] ?? null;
        $compliance->license_verified = $data['license_verified'] ?? false;
        $compliance->user_id = $data['user_id'] ?? null;
        $compliance->active = $data['active'] ?? false;

        $compliance->save();

        return $compliance;
    }

    public function update(string $id, array $data): ?Compliance
    {
        $compliance = $this->findById($id);

        if (!$compliance) {
            return null;
        }

        $compliance->driver_license_front = $data['driver_license_front'] ?? $compliance->driver_license_front;
        $compliance->driver_license_back = $data['driver_license_back'] ?? $compliance->driver_license_back;
        $compliance->photo = $data['photo'] ?? $compliance->photo;
        $compliance->license_state = $data['license_state'] ?? $compliance->license_state;
        $compliance->poster_code = $data['poster_code'] ?? $compliance->poster_code;
        $compliance->license_number = $data['license_number'] ?? $compliance->license_number;
        $compliance->expiration_date = $data['expiration_date'] ?? $compliance->expiration_date;
        $compliance->license_verified = $data['license_verified'] ?? $compliance->license_verified;
        $compliance->user_id = $data['user_id'] ?? $compliance->user_id;

        $compliance->save();

        return $compliance;
    }

    public function delete(string $id): bool
    {
        return Compliance::destroy($id) > 0;
    }

    public function findById(string $id): ?Compliance
    {
        return Compliance::find($id);
    }

    public function findByUserId(string $user_id): Compliance
    {
        return Compliance::where('user_id', $user_id)->first()->get();
    }

    public function markOtherComplianceAsFalse($id)
    {
        Compliance::whereNot('id', $id)->where('user_id', auth()->id())->update([
            'active' => false
        ]);
    }
}
