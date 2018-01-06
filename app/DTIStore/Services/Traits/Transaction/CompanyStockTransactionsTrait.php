<?php namespace App\DTIStore\Services\Traits\Transaction;

trait CompanyStockTransactionsTrait{


    public function addStocksByProductId($productId, $additionalStocks, $userId)
    {
        $transactionCode = $this->addStockCode;

        return $this->addOrRemoveStocksByProductId($productId, $additionalStocks, $userId, $transactionCode);
    }

    public function subtractStocksByProductId($productId, $additionalStocks, $userId)
    {
        $transactionCode = $this->subStockCode;

        return $this->addOrRemoveStocksByProductId($productId, $additionalStocks, $userId, $transactionCode);
    }

    public function addStocksByVariationId($variationId, $quantity, $userId, $remarks = null)
    {
        $transactionCode = $this->addStockCode;

        return $this->addOrRemoveStocksByVariationId($variationId, $quantity, $userId, $transactionCode, $remarks);
    }

    public function subtractStocksByVariationId($variationId, $quantity, $userId, $remarks = null)
    {
        $transactionCode = $this->subStockCode;

        return $this->addOrRemoveStocksByVariationId($variationId, $quantity, $userId, $transactionCode, $remarks);
    }



    private function addOrRemoveStocksByProductId($productId, $additionalStocks, $userId, $transactionCode)
    {
        $transaction = $this->createAdminTransaction($transactionCode, $userId);

        if(!$transaction){
            return false;
        }

        $product = $this->product->find($productId);

        if(!$product){
            return false;
        }

        $transactionId = $transaction->id;
        $variations = $this->variation->getAllByProductId($productId);

        $productName = $product->name;

        $transactionItems = [];

        // get item current quantity snapshot by branch
        $itemsQuantitySnapshots = $this->getItemsCurrentWarehouseQuantitySnapshots($variations);

        foreach($variations as $variation){

            $variationId = $variation->id;

            $productSize = $variation->size;
            $productMetrics = $variation->metrics;
            $productCostPrice = $variation->cost_price;
            $productSellingPrice = $variation->selling_price;
            $productFranchiseePrice = $variation->franchisee_price;

            $currentQuantity = 0;

            if(isset($itemsQuantitySnapshots[$variationId])){
                $currentQuantity = $itemsQuantitySnapshots[$variationId];
            }

            if($transactionCode == $this->subStockCode){
                $currentQuantity = $currentQuantity + $additionalStocks;
                $remainingQuantity = $currentQuantity - $additionalStocks;
            }

            if($transactionCode == $this->addStockCode){
                $currentQuantity = $currentQuantity - $additionalStocks;
                $remainingQuantity = $currentQuantity + $additionalStocks;
            }

            $transactionItems[] = $this->createTransactionItem($transactionId, [
                'product_variation_id' => $variationId,
                'current_quantity' => $currentQuantity,
                'quantity' => $additionalStocks,
                'remaining_quantity' => $remainingQuantity,
                'product_name' => $productName,
                'product_size' => $productSize,
                'product_metrics' => $productMetrics,
                'product_cost_price' => $productCostPrice,
                'product_selling_price' => $productSellingPrice,
                'product_franchisee_price' => $productFranchiseePrice
            ]);

        }

        return [
            'transaction' => $transaction,
            'items' => $transactionItems
        ];
    }

    private function addOrRemoveStocksByVariationId($variationId, $quantity, $userId, $transactionCode, $remarks = null)
    {
        $variation = $this->variation->find($variationId);

        if(!$variation){
            return false;
        }

        $transaction = $this->createAdminTransaction($transactionCode, $userId, [
            'remarks' => $remarks
        ]);

        if(!$transaction){
            return false;
        }

        // get item current quantity snapshot by branch
        $itemsQuantitySnapshots = $this->getItemsCurrentWarehouseQuantitySnapshots([$variation]);

        $transactionId = $transaction->id;

        $productName = $variation->name;
        $productSize = $variation->size;
        $productMetrics = $variation->metrics;
        $productCostPrice = $variation->cost_price;
        $productSellingPrice = $variation->selling_price;
        $productFranchiseePrice = $variation->franchisee_price;

        $currentQuantity = 0;

        if(isset($itemsQuantitySnapshots[$variationId])){
            $currentQuantity = $itemsQuantitySnapshots[$variationId];
        }

        if($transactionCode == $this->subStockCode){
            $currentQuantity = $currentQuantity + $quantity;
            $remainingQuantity = $currentQuantity - $quantity;
        }

        if($transactionCode == $this->addStockCode){
            $currentQuantity = $currentQuantity - $quantity;
            $remainingQuantity = $currentQuantity + $quantity;
        }

        $transactionItem = $this->createTransactionItem($transactionId, [
            'product_variation_id' => $variationId,
            'current_quantity' => $currentQuantity,
            'quantity' => $quantity,
            'remaining_quantity' => $remainingQuantity,
            'product_name' => $productName,
            'product_size' => $productSize,
            'product_metrics' => $productMetrics,
            'product_cost_price' => $productCostPrice,
            'product_selling_price' => $productSellingPrice,
            'product_franchisee_price' => $productFranchiseePrice
        ]);

        return $transactionItem;
    }

}