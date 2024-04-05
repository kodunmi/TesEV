<?php

namespace App\Interfaces\Core;

use App\Models\Trip;

interface TripRepositoryInterface
{
    public function all();

    public function findById(string $id): ?Trip;

    public function create(array $data): Trip;

    public function update(string $id, array $data): ?Trip;

    public function delete(string $id): bool;
}
