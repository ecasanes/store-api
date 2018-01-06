<?php namespace App\DTIStore\Services\Traits\Product;

trait BranchStockTrait{

    public function addBranchStocks($branchId, $items)
    {
        $branchStockDeliveries = [];

        foreach($items as $delivery){

            $variationId = $delivery['product_variation_id'];
            $quantity = $delivery['quantity'];

            $updated = $this->addBranchStock($branchId, $variationId, $quantity);

            $delivery['valid'] = $updated;

            $branchStockDeliveries[] = $delivery;
        }

        return $branchStockDeliveries;
    }

    public function updateBranchStocksCurrentDeliveryQuantity($branchId, $items)
    {
        // assumes that stocks were already added

        $branchStockDeliveries = [];

        foreach($items as $delivery){

            $variationId = $delivery['product_variation_id'];
            $deliveryQuantity = $delivery['quantity'];

            $updated = $this->updateBranchStockCurrentDeliveryQuantityByVariationId($branchId, $variationId);

            $delivery['valid'] = $updated;

            $branchStockDeliveries[] = $delivery;
        }

        return $branchStockDeliveries;
    }

    public function subtractBranchStocksCurrentDeliveryQuantity($branchId, $items)
    {
        $branchStockDeliveries = [];

        foreach($items as $delivery){

            $variationId = $delivery['product_variation_id'];
            $deliveryQuantity = $delivery['quantity'];

            $updated = $this->subtractBranchStockCurrentDeliveryQuantityByVariationId($branchId, $variationId, $deliveryQuantity);

            $delivery['valid'] = $updated;

            $branchStockDeliveries[] = $delivery;
        }

        return $branchStockDeliveries;
    }

    public function subtractBranchStocks($branchId, $items)
    {
        $branchStockReturns = [];

        foreach($items as $delivery){

            $variationId = $delivery['product_variation_id'];
            $quantity = $delivery['quantity'];

            $updated = $this->subtractBranchStock($branchId, $variationId, $quantity);

            $delivery['valid'] = $updated;

            $branchStockReturns[] = $delivery;
        }

        return $branchStockReturns;
    }

    public function addSoldItemsCount($branchId, $items)
    {

        $branchStockSoldItemUpdate = [];

        foreach($items as $item){

            $variationId = $item['product_variation_id'];

            $updated = $this->addSoldItemCountByOne($branchId, $variationId);

            $item['valid'] = $updated;

            $branchStockSoldItemUpdate[] = $item;
        }

        return $branchStockSoldItemUpdate;

    }

    private function addSoldItemCountByOne($branchId, $variationId)
    {

        $updated = $this->branchStock->addSoldItemCountByOne($branchId, $variationId);

        return $updated;

    }

    public function subtractSoldItemsCount($branchId, $items)
    {

        $branchStockSoldItemUpdate = [];

        foreach($items as $item){

            $variationId = $item['product_variation_id'];

            $updated = $this->subtractSoldItemCountByOne($branchId, $variationId);

            $item['valid'] = $updated;

            $branchStockSoldItemUpdate[] = $item;
        }

        return $branchStockSoldItemUpdate;

    }

    private function subtractSoldItemCountByOne($branchId, $variationId)
    {

        $updated = $this->branchStock->subtractSoldItemCountByOne($branchId, $variationId);

        return $updated;

    }

    public function addBranchStocksByShortOver($branchId, $items)
    {
        $branchStockDeliveries = [];

        foreach($items as $delivery){

            $variationId = $delivery['shortover_product_variation_id'];
            $quantity = $delivery['quantity'];

            $updated = $this->addBranchStock($branchId, $variationId, $quantity);

            $delivery['valid'] = $updated;

            $branchStockDeliveries[] = $delivery;
        }

        return $branchStockDeliveries;
    }

    public function subtractBranchStocksByShortOver($branchId, $items)
    {
        $branchStockReturns = [];

        foreach($items as $delivery){

            $variationId = $delivery['shortover_product_variation_id'];
            $quantity = $delivery['quantity'];

            $updated = $this->subtractBranchStock($branchId, $variationId, $quantity);

            $delivery['valid'] = $updated;

            $branchStockReturns[] = $delivery;
        }

        return $branchStockReturns;
    }

    public function updateBranchStockCurrentDeliveryQuantityByVariationId($branchId, $variationId)
    {
        $updated = $this->branchStock->updateCurrentDeliveryQuantityByVariationId($branchId, $variationId);

        return $updated;
    }

    public function subtractBranchStockCurrentDeliveryQuantityByVariationId($branchId, $variationId, $deliveryQuantity)
    {
        $updated = $this->branchStock->subtractCurrentDeliveryQuantityByVariationId($branchId, $variationId, $deliveryQuantity);

        return $updated;
    }

    public function addBranchStock($branchId, $variationId, $quantity)
    {
        $updated = $this->branchStock->addStocksByBranchVariationId($branchId, $variationId, $quantity);

        return $updated;
    }

    public function subtractBranchStock($branchId, $variationId, $quantity)
    {
        $updated = $this->branchStock->subtractStocksByBranchVariationId($branchId, $variationId, $quantity);

        return $updated;
    }

    public function getBranchStocksById($branchId, array $filter = [])
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