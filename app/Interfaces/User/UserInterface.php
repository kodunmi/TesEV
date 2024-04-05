<?php

namespace App\Interfaces\User;

use App\Models\User;

interface UserInterface
{
    public function all();
    public function findById(string $id): User | Null | bool;
    public function findByEmail(string $email): User| Null | bool;
    public function createUser(array $data): User | Null | bool;
    public function updateUser(string $id, array $data): User | Null | bool;
    public function deleteUser(string $id): bool;
}
