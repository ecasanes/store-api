<?php namespace App\DTIStore\Repositories;

interface ProductVariationInterface
{
    public function create(array $data);

    public function find($id);

    public function findWithCompanyStocks($id);

    public function getAll();

    public function filter(array $filter);

    public function update($id, $data);

    public function delete($id);

    public function destroy($id);

    public function isDeleted($id);

    public function getAllByProductId($id);

    public function updateWithProductData($id, $data);
}