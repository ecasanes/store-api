<?php namespace App\Mercury\Repositories;

interface CustomerUserInterface
{
    public function create(array $data);

    public function find($id);

    public function findByCustomerId($customerId);

    public function getAll();

    public function filter(array $filter);

    public function update($id, $data);

    public function delete($id);

    public function destroy($id);

    public function isDeleted($id);

    public function generateCustomerId($userId);

    public function updateCustomerId($userId, $customerId);
}