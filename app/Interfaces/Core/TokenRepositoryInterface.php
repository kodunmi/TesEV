<?php

namespace App\Interfaces\Core;


interface TokenRepositoryInterface
{
    public function getAll();

    public function findById(string $id);

    public function findByToken(string $token);

    public function findUserToken(string $user_id, string $token);

    public function findByTokenAndPhone(string $recipient, string $token);

    public function makeInvalid(string $id);

    public function create(array $data);

    public function update(string $id, array $data);

    public function destroyById(string $id);

    public function destroyUserTokens(string $user_id);
}
