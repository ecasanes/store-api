<?php namespace App\Mercury\Repositories;

interface ActivityLogInterface
{
    public function getAll();

    public function find($id);

    public function filter(array $filter);

    public function getCountAll();

    public function getCountByFilter(array $filter = []);

    public function getFilterMeta($data);

    public function create(array $data);

}