<?php

namespace App\Interfaces\Core;

use App\Models\Building;

interface BuildingRepositoryInterface
{
    public function create(array $data): Building;

    public function update($id, array $data): ?Building;

    public function delete($id): bool;

    public function findById($id): ?Building;

    public function all($search = null);
}
