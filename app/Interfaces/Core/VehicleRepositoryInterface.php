<?php

namespace App\Interfaces\Core;

interface VehicleRepositoryInterface
{
    public function all($search = null);

    public function findById($id);

    public function create(array $data);

    public function update($id, array $data);

    public function delete($id);
}
