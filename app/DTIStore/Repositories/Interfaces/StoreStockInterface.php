<?php namespace App\DTIStore\Repositories;

interface StoreStockInterface
{
    public function create(array $data);

    public function find($id);

    public function findByBranchVariationId($branchId, $variationId);

    public function getAll();

    public function filter(array $filter);

    public function update($id, $data);

    public function delete($id);

    public function destroy($id);

    public function isDeleted($id);

    public function addStocksByBranchVariationId($branchId, $variationId, $quantity);

    public function subtractStocksByBranchVariationId($branchId, $variationId, $quantity);

    public function getBranchStocksByItemIds($branchId, array $itemIds);

    public function getBranchStocksById($branchId, array $filter);
}