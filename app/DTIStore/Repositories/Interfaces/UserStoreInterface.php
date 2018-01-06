<?php namespace App\DTIStore\Repositories;

interface UserStoreInterface
{
    public function create(array $data);

    public function find($id);

    public function getAll();

    public function filter(array $filter);

    public function update($id, $data);

    public function updateByUserId($userId, $StoreId);
}