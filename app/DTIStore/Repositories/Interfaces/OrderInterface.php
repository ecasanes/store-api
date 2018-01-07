<?php namespace App\DTIStore\Repositories;

interface OrderInterface
{
    public function create($data);

    public function find($id);

    public function getAll();

    public function update($id, $data);

    public function delete($id);

    public function destroy($id);

    public function isDeleted($id);

    public function getAllBuyersOrderHistory($filter);
}