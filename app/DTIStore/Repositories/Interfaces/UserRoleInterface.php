<?php namespace App\DTIStore\Repositories;

interface UserRoleInterface
{
    public function create(array $data);

    public function find($id);

    public function getAll();

    public function filter(array $filter);

    public function update($id, $data);

    public function getRolesByUserId($userId);

    public function getRoleByUserId($userId);

    public function findRoleByUserId($userId);

    public function hasPermissions($userId);
}