<?php

namespace App\Repositories\Core;

use App\Interfaces\Core\TripRepositoryInterface;
use App\Models\Trip;

class TripRepository implements TripRepositoryInterface
{
    public function all()
    {
        return Trip::paginate(10);
    }

    public function findById(string $id): ?Trip
    {
        return Trip::find($id);
    }

    public function create(array $data): Trip
    {
        $trip = new Trip();
        $trip->user_id = $data['user_id'] ?? null;
        $trip->vehicle_id = $data['vehicle_id'] ?? null;
        $trip->start_time = $data['start_time'] ?? null;
        $trip->end_time = $data['end_time'] ?? null;
        $trip->parent_trip_id = $data['parent_trip_id'] ?? null;
        $trip->status = $data['status'] ?? 'pending';
        $trip->tax_amount = $data['tax_amount'] ?? 0;
        $trip->tax_percentage = $data['tax_percentage'] ?? 0;

        $trip->public_id = uuid();
        $trip->booking_id = generateRandomNumber(10);

        $trip->save();
        return $trip;
    }

    public function update(string $id, array $data): ?Trip
    {
        $trip = $this->findById($id);

        if (!$trip) {
            return null;
        }

        $trip->user_id = $data['user_id'] ?? $trip->user_id;
        $trip->vehicle_id = $data['vehicle_id'] ?? $trip->vehicle_id;
        $trip->start_time = $data['start_time'] ??  $trip->start_time;
        $trip->end_time = $data['end_time'] ??  $trip->end_time;
        $trip->parent_trip_id = $data['parent_trip_id'] ?? $trip->parent_trip_id;
        $trip->status = $data['status'] ??  $trip->status;

        $trip->save();
        return $trip;
    }

    public function delete(string $id): bool
    {
        $trip = Trip::find($id);
        if ($trip) {
            $trip->delete();
            return true;
        }
        return false;
    }

    public function query()
    {
        return Trip::query();
    }
}
