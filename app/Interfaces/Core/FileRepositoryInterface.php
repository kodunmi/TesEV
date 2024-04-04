<?php
namespace App\Interfaces\Core;


interface FileRepositoryInterface
{
    public function create(array $data);

    public function update(string $id, array $data);

    public function delete($id);

    public function findById($id);

    public function findByPath($path);

    public function findByURL($url);

    public function all();

    public function query();
}
