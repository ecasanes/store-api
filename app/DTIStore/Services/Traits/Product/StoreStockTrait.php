<?php namespace App\DTIStore\Services\Traits\Product;

trait StoreStockTrait{

    public function addStoreStocks($storeId, $items)
    {
        $branchStockDeliveries = [];

        foreach($items as $delivery){

            $variationId = $delivery['product_variation_id'];
            $quantity = $delivery['quantity'];

            $updated = $this->addStoreStock($storeId, $variationId, $quantity);

            $delivery['valid'] = $updated;

            $branchStockDeliveries[] = $delivery;
        }

        return $branchStockDeliveries;
    }

    public function subtractStoreStocks($storeId, $items)
    {
        $branchStockReturns = [];

        foreach($items as $delivery){

            $variationId = $delivery['product_variation_id'];
            $quantity = $delivery['quantity'];

            $updated = $this->subtractStoreStock($storeId, $variationId, $quantity);

            $delivery['valid'] = $updated;

            $branchStockReturns[] = $delivery;
        }

        return $branchStockReturns;
    }

    public function addStoreStock($branchId, $variationId, $quantity)
    {
        $updated = $this->branchStock->addStocksByBranchVariationId($branchId, $variationId, $quantity);

        return $updated;
    }

    public function subtractStoreStock($branchId, $variationId, $quantity)
    {
        $updated = $this->branchStock->subtractStocksByBranchVariationId($branchId, $variationId, $quantity);

        return $updated;
    }

    public function getStoreStocksById($branchId, array $filter = [])
    {
        $branchStocks = $this->branchStock->getBranchStocksById($branchId, $filter);

        return $branchStocks;
    }

    public function validateEnoughBranchStocks($branchId, $items)
    {
        $itemValidations = [];

        foreach($items as $delivery){

            $productVariationId = $delivery['product_variation_id'];
            $quantity = $delivery['quantity'];

            $isValid = $this->isVariationHasEnoughBranchStocks($branchId, $productVariationId, $quantity);

            $delivery['valid'] = $isValid;

            $itemValidations[] = $delivery;
        }

        return $itemValidations;

    }

    public function isVariationHasEnoughBranchStocks($branchId, $variationId, $quantity)
    {
        if($quantity < 0){
            return false;
        }

        $branchStock = $this->branchStock->findByBranchVariationId($branchId, $variationId);

        if(!$branchStock){
            return false;
        }

        $currentQuantity = $branchStock->quantity;

        if($currentQuantity < $quantity){
            return false;
        }

        return true;
    }

}