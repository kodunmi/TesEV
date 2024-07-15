<?php

namespace App\Repositories\Core;

use App\Interfaces\Core\BuildingRepositoryInterface;
use App\Models\Building;

class BuildingRepository implements BuildingRepositoryInterface
{
    public function all($search = null)
    {
        $query = Building::where('status', 'active');

        if ($search) {
            $query->where(function ($subQuery) use ($search) {
                $subQuery->where('name', 'like', '%' . $search . '%')
                    ->orWhere('address', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%')
                    ->orWhere('code', 'like', '%' . $search . '%');
            });
        }

        return $query->paginate(10);
    }

    public function allWithoutPag($search = null)
    {
        $query = Building::query();

        if ($search) {
            $query->where(function ($subQuery) use ($search) {
                $subQuery->where('name', 'like', '%' . $search . '%')
                    ->orWhere('address', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%')
                    ->orWhere('code', 'like', '%' . $search . '%');
            });
        }

        return $query->get();
    }

    public function create(array $data): Building
    {
        print_r($data);
        $building = new Building();

        $building->public_id = uuid(); // Generating a UUID for the public identifier
        $building->code = generateRandomNumber(6);
        $building->name = $data['name'] ?? null;
        $building->address = $data['address'] ?? null;
        $building->opening_time = $data['opening_time'] ?? null;
        $building->closing_time = $data['closing_time'] ?? null;
        $building->status = $data['status'] ?? null;
        $building->image = $data['image'] ?? null;
        $building->description = $data['description'] ?? null;
        $building->type = $data['type'] ?? null;
        $building->floor_count = $data['floor_count'] ?? null;
        $building->built_year = $data['built_year'] ?? null;
        $building->contact_person = $data['contact_person'] ?? null;
        $building->contact_email = $data['contact_email'] ?? null;
        $building->contact_phone = $data['contact_phone'] ?? null;
        $building->latitude = $data['latitude'] ?? null;
        $building->longitude = $data['longitude'] ?? null;
        $building->construction_material = $data['construction_material'] ?? null;
        $building->architect = $data['architect'] ?? null;
        $building->construction_company = $data['construction_company'] ?? null;
        $building->maintenance_company = $data['maintenance_company'] ?? null;
        $building->security_level = $data['security_level'] ?? null;
        $building->insurance_policy_number = $data['insurance_policy_number'] ?? null;
        $building->last_inspection_date = $data['last_inspection_date'] ?? null;

        print_r($building);

        $building->save();

        return $building;
    }

    public function update($id, array $data): ?Building
    {
        $building = $this->findById($id);
        if (!$building) {
            return null;
        }

        $building->name = $data['name'] ?? $building->name;
        $building->address = $data['address'] ?? $building->address;
        $building->opening_time = $data['opening_time'] ?? $building->opening_time;
        $building->closing_time = $data['closing_time'] ?? $building->closing_time;
        $building->status = $data['status'] ?? $building->status;
        $building->image = $data['image'] ?? $building->image;
        $building->description = $data['description'] ?? $building->description;
        $building->type = $data['type'] ?? $building->type;
        $building->floor_count = $data['floor_count'] ?? $building->floor_count;
        $building->built_year = $data['built_year'] ?? $building->built_year;
        $building->contact_person = $data['contact_person'] ?? $building->contact_person;
        $building->contact_email = $data['contact_email'] ?? $building->contact_email;
        $building->contact_phone = $data['contact_phone'] ?? $building->contact_phone;
        $building->latitude = $data['latitude'] ?? $building->latitude;
        $building->longitude = $data['longitude'] ?? $building->longitude;
        $building->construction_material = $data['construction_material'] ?? $building->construction_material;
        $building->architect = $data['architect'] ?? $building->architect;
        $building->construction_company = $data['construction_company'] ?? $building->construction_company;
        $building->maintenance_company = $data['maintenance_company'] ?? $building->maintenance_company;
        $building->security_level = $data['security_level'] ?? $building->security_level;
        $building->insurance_policy_number = $data['insurance_policy_number'] ?? $building->insurance_policy_number;
        $building->last_inspection_date = $data['last_inspection_date'] ?? $building->last_inspection_date;

        $building->save();
        return $building;
    }

    public function delete($id): bool
    {
        $building = $this->findById($id);
        if ($building) {
            $building->delete();
            return true;
        }
        return false;
    }

    public function findById($id): ?Building
    {
        return Building::find($id);
    }

    public function toggleById($id): ?Building
    {
        $building = $this->findById($id);

        $building->status = $building->status == 'active' ?  'inactive' : 'active';

        $building->save();

        return $building;
    }
}
