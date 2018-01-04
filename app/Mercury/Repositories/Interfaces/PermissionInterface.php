<?php namespace App\Mercury\Repositories;

interface PermissionInterface
{
    public function create(array $data);

    public function find($id);

    public function getAll();

    public function filter(array $filter);

    public function update($id, $data);
}