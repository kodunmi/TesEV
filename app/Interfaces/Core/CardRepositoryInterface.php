<?php

namespace App\Interfaces\Core;

use App\Models\Card;

interface CardRepositoryInterface
{
    public function all();

    public function findById(string $id): ?Card;

    public function create(array $data): Card;

    public function update(string $id, array $data): ?Card;

    public function delete(string $id): bool;
}
