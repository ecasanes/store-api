<?php namespace App\Mercury\Repositories;

interface DeliveryInterface
{
    public function create(array $data);

    public function find($id);

    public function getAll();

    public function getCountAll();

    public function filter(array $filter);

    public function update($id, $data);

    public function delete($id);

    public function destroy($id);

    public function isDeleted($id);

    public function getFilterMeta($data);

    public function updateStatus($id, $status);

    public function getCountByFilter(array $filter);
}