<?php

namespace App\Interfaces\User;

use App\Models\User;

interface UserInterface
{
    public function getUsers();
    public function getUserById(string $id): User | Null | bool;
    public function getUserByEmail(string $email): User| Null | bool;
    public function createUser(array $data): User | Null | bool;
    public function updateUser(string $id, array $data): User | Null | bool;
    public function deleteUser(string $id): bool;
}
