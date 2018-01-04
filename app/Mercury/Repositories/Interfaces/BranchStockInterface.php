<?php namespace App\Mercury\Repositories;

interface BranchStockInterface
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

    public function getAlerts($filter, $threshold);

    public function getAlertsByItemIds(array $itemIds, $threshold);

    public function updateCurrentDeliveryQuantityByVariationId($branchId, $variationId);

    public function subtractCurrentDeliveryQuantityByVariationId($branchId, $variationId, $deliveryQuantity);

    public function addSoldItemCountByOne($branchId, $variationId);

    public function subtractSoldItemCountByOne($branchId, $variationId);
}