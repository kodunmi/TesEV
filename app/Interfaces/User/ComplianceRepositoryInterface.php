<?php

namespace App\Interfaces\User;

use App\Models\Compliance;

interface ComplianceRepositoryInterface
{
    public function create(array $data): Compliance;

    public function update(string $id, array $data): ?Compliance;

    public function delete(string $id): bool;

    public function findById(string $id): ?Compliance;

    public function markOtherComplianceAsFalse(string $id);

    public function findByUserId(string $user_id): Compliance;
}
