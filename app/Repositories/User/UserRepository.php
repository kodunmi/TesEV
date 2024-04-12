<?php

namespace App\Repositories\User;

use App\Interfaces\User\UserInterface;
use App\Models\User;

class UserRepository implements UserInterface
{

    public function all()
    {
    }
    public function findById(string $id): User
    {
        return User::find($id);
    }

    public function findByEmail(string $email): User
    {
        return User::where('email', $email)->first();
    }

    public function createUser(array $data): ?User
    {
        try {
            $user = new User();
            $user->first_name = $data['first_name'] ?? null;
            $user->last_name = $data['last_name'] ?? null;
            $user->email = $data['email'] ?? null;
            $user->phone = $data['phone'] ?? null;
            $user->phone_code = $data['phone_code'] ?? null;
            $user->pin = $data['pin'] ?? null;
            $user->gender = $data['gender'] ?? null;
            $user->status = $data['status'] ?? 'pending'; // default to 'pending' if not provided
            $user->date_of_birth = $data['date_of_birth'] ?? null;
            $user->password = $data['password'] ?? null;

            $user->save();

            return $user;
        } catch (\Throwable $th) {
            logError($th->getMessage());
            return null;
        }
    }

    public function updateUser(string $id, array $data): ?User
    {
        try {
            $user = $this->findById($id);

            $user->first_name = $data['first_name'] ?? $user->first_name;
            $user->last_name = $data['last_name'] ?? $user->last_name;
            $user->email = $data['email'] ?? $user->email;
            $user->phone = $data['phone'] ?? $user->phone;
            $user->phone_code = $data['phone_code'] ?? $user->phone_code;
            $user->pin = $data['pin'] ?? $user->pin;
            $user->gender = $data['gender'] ?? $user->gender;
            $user->wallet = $data['wallet'] ?? $user->wallet;
            $user->status = $data['status'] ?? $user->status;
            $user->date_of_birth = $data['date_of_birth'] ?? $user->date_of_birth;
            $user->password = isset($data['password']) ? hashData($data['password'])  : $user->password;
            $user->email_verified_at = $data['email_verified_at'] ?? $user->email_verified_at;

            $user->save();

            return $user;
        } catch (\Throwable $th) {
            logError($th->getMessage());
            return null;
        }
    }

    public function deleteUser(string $id): bool
    {
        try {
            $user = $this->findById($id);

            return $user->delete();

            return $user;
        } catch (\Throwable $th) {
            return false;
        }
    }
}
