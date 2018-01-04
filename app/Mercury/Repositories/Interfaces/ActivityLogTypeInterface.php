<?php namespace App\Mercury\Repositories;

interface ActivityLogTypeInterface
{
    public function getAll();

    public function find($id);

    public function findByCode($code);

    public function findByRoleId($roleId);

    public function filter(array $filter);
}