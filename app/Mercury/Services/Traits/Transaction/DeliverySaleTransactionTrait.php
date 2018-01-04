<?php namespace App\Mercury\Services\Traits\Transaction;

use App\Mercury\Helpers\StatusHelper;

trait DeliverySaleTransactionTrait{

    public function createDeliverySaleTransaction($branchId, $items, $userId, $invoiceNo = null, $remarks = null)
    {
        $saleTransactionCode = $this->sale;
        $subType = StatusHelper::SUB_TYPE_DELIVERY;
        $franchiseeFlag = StatusHelper::FRANCHISEE;

        $transaction = $this->createAdminTransaction($saleTransactionCode, $userId, [
            'branch_id' => $branchId,
            'invoice_no' => $invoiceNo,
            'sub_type' => $subType,
            'branch_type' => $franchiseeFlag,
            'remarks' => $remarks
        ]);

        if(!$transaction){
            return false;
        }

        $transactionId = $transaction->id;
        $grandTotal = 0;

        $transactionItems = [];

        // get item current quantity snapshot by branch
        $branchItemsQuantitySnapshots = $this->getItemsCurrentQuantitySnapshotsByBranch($branchId, $items);
        $warehouseItemsQuantitySnapshots = $this->getItemsCurrentWarehouseQuantitySnapshots($items);

        foreach($items as $item){

            $variationId = $item['product_variation_id'];
            $quantity = $item['quantity'];

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

            $grandTotal += $quantity*$productFranchiseePrice;

        }

        $updated = $this->transaction->update($transactionId, [
            'grand_total' => $grandTotal
        ]);

        return [
            'transaction' => $transaction,
            'items' => $transactionItems
        ];
    }

    public function createReturnDeliveryReturnSaleTransaction($branchId, $items, $userId, $invoiceNo = null, $remarks = null)
    {

        $returnSaleTransactionCode = $this->returnSale;
        $subType = StatusHelper::SUB_TYPE_DELIVERY;
        $franchiseeFlag = StatusHelper::FRANCHISEE;

        $transaction = $this->createAdminTransaction($returnSaleTransactionCode, $userId, [
            'invoice_no' => $invoiceNo,
            'branch_id' => $branchId,
            'sub_type' => $subType,
            'branch_type' => $franchiseeFlag,
            'remarks' => $remarks
        ]);

        if(!$transaction){
            return false;
        }

        $transactionId = $transaction->id;

        $transactionItems = [];

        $branchItemsQuantitySnapshots = $this->getItemsCurrentQuantitySnapshotsByBranch($branchId, $items);
        $warehouseItemsQuantitySnapshots = $this->getItemsCurrentWarehouseQuantitySnapshots($items);

        foreach($items as $item){

            $variationId = $item['product_variation_id'];
            $quantity = $item['quantity'];

            if($quantity<=0){
                continue;
            }

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

    public function hasExistingSaleByInvoiceNo($invoiceNo)
    {
        $transactions = $this->transaction->getByInvoiceNo($invoiceNo);

        if(count($transactions) <= 0){
            return false;
        }

        return true;
    }

    public function hasEnoughReturnQuantitiesByInvoiceNo($invoiceNo, $deliveries)
    {
        $validatedDeliveries = [];

        foreach($deliveries as $delivery) {

            $delivery['valid'] = $this->hasEnoughReturnQuantityByInvoiceNo($invoiceNo, $delivery);

            $validatedDeliveries[] = $delivery;

        }

        return $validatedDeliveries;

    }

    private function hasEnoughReturnQuantityByInvoiceNo($invoiceNo, $delivery) {

        $productVariationid = $delivery['product_variation_id'];
        $quantity = $delivery['quantity'];

        // get what's left of the delivery sale minus delivery return sale for this particular product variation id - given invoice no
        $remainingQuantity = $this->transaction->getProductRemainingDeliverySaleQuantityByInvoiceNo($productVariationid, $invoiceNo);

        if($quantity>$remainingQuantity){
            return false;
        }

        return true;

    }

}