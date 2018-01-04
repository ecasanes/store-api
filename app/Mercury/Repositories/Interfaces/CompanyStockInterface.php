<?php namespace App\Mercury\Repositories;

interface CompanyStockInterface
{
    public function create(array $data);

    public function find($id);

    public function getAll();

    public function filter(array $filter);

    public function update($id, $data);

    public function delete($id);

    public function destroy($id);

    public function isDeleted($id);

    public function addStocksByProductId($productId, $additionalStocks);

    public function subtractStocksByProductId($productId, $additionalStocks);

    public function addStocksByVariationId($variationId, $quantity);

    public function subtractStocksByVariationId($variationId, $quantity);

    public function findByVariationId($variationId);

    public function getCompanyStocksByItemIds(array $itemIds, $companyId = null);
}