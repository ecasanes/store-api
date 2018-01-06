<?php namespace App\DTIStore\Services\Traits\Transaction;

use App\DTIStore\Helpers\StatusHelper;

trait FranchiseeSaleTransactionTrait{

    public function createFranchiseeSaleTransaction($branchId, $items, $userId, $invoiceNo = null)
    {
        $saleTransactionCode = $this->sale;
        $subType = StatusHelper::SUB_TYPE_DELIVERY;

        $transaction = $this->createAdminTransaction($saleTransactionCode, $userId, [
            'branch_id' => $branchId,
            'invoice_no' => $invoiceNo,
            'sub_type' => $subType
        ]);

        if(!$transaction){
            return false;
        }

        $transactionId = $transaction->id;
        $grandTotal = 0;

        $transactionItems = [];

        // get item current quantity snapshot by branch
        $itemsQuantitySnapshots = $this->getItemsCurrentQuantitySnapshotsByBranch($branchId, $items);

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

            if(isset($itemsQuantitySnapshots[$variationId])){
                $currentQuantity = $itemsQuantitySnapshots[$variationId] + $quantity;
            }

            $remainingQuantity = $currentQuantity - $quantity;

            $transactionItems[] = $this->createTransactionItem($transactionId, [
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

            $grandTotal += $quantity*$productSellingPrice;

        }

        $updated = $this->transaction->update($transactionId, [
            'grand_total' => $grandTotal
        ]);

        return [
            'transaction' => $transaction,
            'items' => $transactionItems
        ];
    }

    public function createFranchiseeReturnSaleTransaction($branchId, $items, $userId, $invoiceNo = null)
    {

        $returnSaleTransactionCode = $this->returnSale;
        $subType = StatusHelper::SUB_TYPE_DELIVERY;

        $transaction = $this->createAdminTransaction($returnSaleTransactionCode, $userId, [
            'invoice_no' => $invoiceNo,
            'branch_id' => $branchId,
            'sub_type' => $subType
        ]);

        if(!$transaction){
            return false;
        }

        $transactionId = $transaction->id;

        $transactionItems = [];

        // get item current quantity snapshot by branch
        $itemsQuantitySnapshots = $this->getItemsCurrentQuantitySnapshotsByBranch($branchId, $items);

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

            if(isset($itemsQuantitySnapshots[$variationId])){
                $currentQuantity = $itemsQuantitySnapshots[$variationId] - $quantity;
            }

            $remainingQuantity = $currentQuantity + $quantity;

            $transactionItems[] = $this->createTransactionItem($transactionId, [
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

        }

        return [
            'transaction' => $transaction,
            'items' => $transactionItems
        ];

    }

}