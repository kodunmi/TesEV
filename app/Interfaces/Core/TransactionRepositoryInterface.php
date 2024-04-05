<?php

namespace App\Interfaces\Core;

interface TransactionRepositoryInterface
{
    public function create(array $data);

    public function update(string $id, array $data);

    public function delete(string $id);

    public function findById(string $id);

    public function findManyById(array $array);

    public function all();

    public function query();
}
