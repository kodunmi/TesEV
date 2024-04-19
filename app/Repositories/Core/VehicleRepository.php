<?php

namespace App\Repositories\Core;

use App\Interfaces\Core\VehicleRepositoryInterface;
use App\Models\Vehicle;

class VehicleRepository implements VehicleRepositoryInterface
{
    public function all($search = null)
    {
        $query = Vehicle::query();

        if ($search) {
            $query->where(function ($subQuery) use ($search) {
                $subQuery->where('name', 'like', '%' . $search . '%')
                    ->orWhere('color', 'like', '%' . $search . '%');
            });
        }

        return $query->paginate(10);
    }

    public function findById($id)
    {
        return Vehicle::find($id);
    }

    public function create(array $data)
    {
        $vehicle = new Vehicle();

        $vehicle->name = $data['name'] ?? null;
        $vehicle->color = $data['color'] ?? null;
        $vehicle->status = $data['status'] ?? null;
        $vehicle->price_per_hour = $data['price_per_hour'] ?? null;
        $vehicle->image = $data['image'] ?? null;
        $vehicle->plate_number = $data['plate_number'] ?? null;
        $vehicle->battery_capacity = $data['battery_capacity'] ?? null;
        $vehicle->charging_time = $data['charging_time'] ?? null;
        $vehicle->range = $data['range'] ?? null;
        $vehicle->power_output = $data['power_output'] ?? null;
        $vehicle->acceleration = $data['acceleration'] ?? null;
        $vehicle->charging_connector_type = $data['charging_connector_type'] ?? null;
        $vehicle->energy_efficiency = $data['energy_efficiency'] ?? null;
        $vehicle->charging_network = $data['charging_network'] ?? null;
        $vehicle->battery_warranty = $data['battery_warranty'] ?? null;
        $vehicle->building_id = $data['building_id'] ?? null;

        $vehicle->public_id = uuid();

        $vehicle->save();

        return $vehicle;
    }

    public function update($id, array $data)
    {
        $vehicle = $this->findById($id);
        if (!$vehicle) {
            return null;
        }

        $vehicle->name = $data['name'] ?? $vehicle->name;
        $vehicle->color = $data['color'] ?? $vehicle->color;
        $vehicle->status = $data['status'] ?? $vehicle->status;
        $vehicle->price_per_hour = $data['price_per_hour'] ?? $vehicle->price_per_hour;
        $vehicle->image = $data['image'] ?? $vehicle->image;
        $vehicle->plate_number = $data['plate_number'] ?? $vehicle->plate_number;
        $vehicle->battery_capacity = $data['battery_capacity'] ?? $vehicle->battery_capacity;
        $vehicle->charging_time = $data['charging_time'] ?? $vehicle->charging_time;
        $vehicle->range = $data['range'] ?? $vehicle->range;
        $vehicle->power_output = $data['power_output'] ?? $vehicle->power_output;
        $vehicle->acceleration = $data['acceleration'] ?? $vehicle->acceleration;
        $vehicle->charging_connector_type = $data['charging_connector_type'] ?? $vehicle->charging_connector_type;
        $vehicle->energy_efficiency = $data['energy_efficiency'] ?? $vehicle->energy_efficiency;
        $vehicle->charging_network = $data['charging_network'] ?? $vehicle->charging_network;
        $vehicle->battery_warranty = $data['battery_warranty'] ?? $vehicle->battery_warranty;

        $vehicle->building_id = $data['building_id'] ?? $vehicle->building_id;

        $vehicle->save();
        return $vehicle;
    }

    public function delete($id)
    {
        $vehicle = $this->findById($id);
        if ($vehicle) {
            $vehicle->delete();
            return true;
        }
        return false;
    }
}
