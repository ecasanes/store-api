<?php namespace App\Mercury\Repositories;

interface UserPermissionInterface
{
    public function create(array $data);

    public function find($id);

    public function getAll();

    public function filter(array $filter);

    public function update($id, $data);

    public function getPermissionsByUserId($userId);

    public function createUserPermissionsByCode($userId, $permissionCodes);

    public function updateUserPermissionByCode($userId, $permissions);
}