<?php

namespace App\Repositories\Admin;

use App\Models\Admin;

class AdminRepository
{
    public function all()
    {
        return Admin::paginate(10);
    }
    public function findById(string $id): Admin
    {
        return Admin::find($id);
    }

    public function findByEmail(string $email): Admin|null
    {
        return Admin::where('email', $email)->first();
    }

    public function createAdmin(array $data): ?Admin
    {
        try {
            $admin = new Admin();
            $admin->first_name = $data['first_name'] ?? null;
            $admin->last_name = $data['last_name'] ?? null;
            $admin->email = $data['email'] ?? null;
            $admin->password = $data['password'] ?? null;

            $admin->save();

            return $admin;
        } catch (\Throwable $th) {
            logError($th->getMessage());
            return null;
        }
    }

    public function updateAdmin(string $id, array $data): ?Admin
    {
        try {
            $admin = $this->findById($id);

            if (!$admin) {
                return false;
            }

            $admin->first_name = $data['first_name'] ?? $admin->first_name;
            $admin->last_name = $data['last_name'] ?? $admin->last_name;
            $admin->email = $data['email'] ?? $admin->email;
            $admin->role = $data['role'] ?? $admin->role;
            $admin->active = $data['active'] ?? $admin->active;
            $admin->password = isset($data['password']) ? hashData($data['password'])  : $admin->password;

            $admin->save();

            return $admin;
        } catch (\Throwable $th) {
            logError($th->getMessage());
            return null;
        }
    }

    public function deleteAdmin(string $id): bool
    {
        try {
            $admin = $this->findById($id);

            if (!$admin) {
                return false;
            }

            return $admin->delete();

            return true;
        } catch (\Throwable $th) {
            return false;
        }
    }

    public function query()
    {
        return Admin::query();
    }
}
