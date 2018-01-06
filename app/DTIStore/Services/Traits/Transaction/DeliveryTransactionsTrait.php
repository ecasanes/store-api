<?php namespace App\DTIStore\Services\Traits\Transaction;

use App\DTIStore\Helpers\StatusHelper;

trait DeliveryTransactionsTrait{

    public function deliverStocksToBranches($branchId, $deliveryItems, $userId, $remarks = null)
    {
        $transactionCode = $this->deliverStockCode;
        $franchiseeFlag = null;

        $isFranchisee = $this->isBranchFranchisee($branchId);

        if($isFranchisee){
            $franchiseeFlag = StatusHelper::FRANCHISEE;
        }

        $transaction = $this->createAdminTransaction($transactionCode, $userId, [
            'branch_id' => $branchId,
            'branch_type' => $franchiseeFlag,
            'remarks' => $remarks
        ]);

        if(!$transaction){
            return false;
        }

        $transactionId = $transaction->id;

        $transactionItems = [];

        // get item current quantity snapshot by branch
        $branchItemsQuantitySnapshots = $this->getItemsCurrentQuantitySnapshotsByBranch($branchId, $deliveryItems);
        $warehouseItemsQuantitySnapshots = $this->getItemsCurrentWarehouseQuantitySnapshots($deliveryItems);

        foreach($deliveryItems as $deliveryItem){

            $variationId = $deliveryItem['product_variation_id'];
            $quantity = $deliveryItem['quantity'];

            $variation = $this->variation->find($variationId);

            $productName = $variation->name;
            $productSize = $variation->size;
            $productMetrics = $variation->metrics;
            $productCostPrice = $variation->cost_price;
            $productSellingPrice = $variation->selling_price;
            $productFranchiseePrice = $variation->franchisee_price;

            $currentQuantity = 0;
            $currentWarehouseQuantity = 0;

            if(isset($branchItemsQuantitySnapshots[$variationId])){
                // added by the quantity since transaction is recorded after addition/subtraction of any stocks
                $currentQuantity = $branchItemsQuantitySnapshots[$variationId] - $quantity;
            }

            if(isset($warehouseItemsQuantitySnapshots[$variationId])){
                $currentWarehouseQuantity = $warehouseItemsQuantitySnapshots[$variationId] + $quantity;
            }

            $remainingQuantity = $currentQuantity + $quantity;
            $remainingWarehouseQuantity = $currentWarehouseQuantity - $quantity;

            $transactionItems[] = $this->createTransactionItem($transactionId, [
                'product_variation_id' => $variationId,
                'current_quantity' => $currentQuantity,
                'current_warehouse_quantity' => $currentWarehouseQuantity,
                'quantity' => $quantity,
                'remaining_quantity' => $remainingQuantity,
                'remaining_warehouse_quantity' => $remainingWarehouseQuantity,
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

    public function returnStocksToCompany($branchId, $deliveryItems, $userId, $remarks = null)
    {
        $transactionCode = $this->returnStockCode;
        $franchiseeFlag = null;

        $isFranchisee = $this->isBranchFranchisee($branchId);

        if($isFranchisee){
            $franchiseeFlag = StatusHelper::FRANCHISEE;
        }

        $transaction = $this->createAdminTransaction($transactionCode, $userId, [
            'branch_id' => $branchId,
            'branch_type' => $franchiseeFlag,
            'remarks' => $remarks
        ]);

        if(!$transaction){
            return false;
        }

        $transactionId = $transaction->id;

        $transactionItems = [];

        // get item current quantity snapshot by branch
        $branchItemsQuantitySnapshots = $this->getItemsCurrentQuantitySnapshotsByBranch($branchId, $deliveryItems);
        $warehouseItemsQuantitySnapshots = $this->getItemsCurrentWarehouseQuantitySnapshots($deliveryItems);

        foreach($deliveryItems as $deliveryItem){

            $variationId = $deliveryItem['product_variation_id'];
            $quantity = $deliveryItem['quantity'];

            $variation = $this->variation->find($variationId);

            $productName = $variation->name;
            $productSize = $variation->size;
            $productMetrics = $variation->metrics;
            $productCostPrice = $variation->cost_price;
            $productSellingPrice = $variation->selling_price;
            $productFranchiseePrice = $variation->franchisee_price;

            $currentQuantity = 0;
            $currentWarehouseQuantity = 0;

            if(isset($branchItemsQuantitySnapshots[$variationId])){
                // added by the quantity since transaction is recorded after addition/subtraction of any stocks
                $currentQuantity = $branchItemsQuantitySnapshots[$variationId] + $quantity;
            }

            if(isset($warehouseItemsQuantitySnapshots[$variationId])){
                $currentWarehouseQuantity = $warehouseItemsQuantitySnapshots[$variationId] - $quantity;
            }

            $remainingQuantity = $currentQuantity - $quantity;
            $remainingWarehouseQuantity = $currentWarehouseQuantity + $quantity;

            $transactionItems[] = $this->createTransactionItem($transactionId, [
                'product_variation_id' => $variationId,
                'current_quantity' => $currentQuantity,
                'current_warehouse_quantity' => $currentWarehouseQuantity,
                'quantity' => $quantity,
                'remaining_quantity' => $remainingQuantity,
                'remaining_warehouse_quantity' => $remainingWarehouseQuantity,
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

}