<?php namespace App\DTIStore\Services\Traits\Transaction;

trait BranchStockTransactionsTrait{

    public function createAdjustmentShortTransaction($branchId, $items, $staffId, $userId = null)
    {

        $transactionCode = $this->adjustmentShort;

        $additionalTransactionData = [
            'branch_id' => $branchId
        ];


        if(!$staffId){
            $transaction = $this->createAdminTransaction($transactionCode, $userId, $additionalTransactionData);
        }

        if($staffId){
            $transaction = $this->createStaffTransaction($transactionCode, $staffId, $additionalTransactionData);
        }

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

        }

        return [
            'transaction' => $transaction,
            'items' => $transactionItems
        ];

    }

    public function createAdjustmentShortOverTransaction($orNumber, $branchId, $items, $staffId, $userId = null)
    {

        $transactionCode = $this->adjustmentShortover;

        $additionalTransactionData = [
            'or_no' => $orNumber,
            'branch_id' => $branchId
        ];


        if(!$staffId){
            $transaction = $this->createAdminTransaction($transactionCode, $userId, $additionalTransactionData);
        }

        if($staffId){
            $transaction = $this->createStaffTransaction($transactionCode, $staffId, $additionalTransactionData);
        }

        if(!$transaction){
            return false;
        }

        $transactionId = $transaction->id;

        $transactionItems = [];

        // get item current quantity snapshot by branch
        $itemsQuantitySnapshots = $this->getItemsCurrentQuantitySnapshotsByBranch($branchId, $items);

        foreach($items as $item){

            $variationId = $item['product_variation_id'];
            $shortOverVariationId = $item['shortover_product_variation_id'];
            $quantity = $item['quantity'];

            $variation = $this->variation->find($variationId);
            $shortOverVariation = $this->variation->find($shortOverVariationId);

            $productName = $variation->name;
            $productSize = $variation->size;
            $productMetrics = $variation->metrics;
            $productCostPrice = $variation->cost_price;
            $productSellingPrice = $variation->selling_price;
            $productFranchiseePrice = $variation->franchisee_price;

            $shortoverProductName = $shortOverVariation->name;
            $shortoverProductSize = $shortOverVariation->size;
            $shortoverProductMetrics = $shortOverVariation->metrics;
            $shortoverProductCostPrice = $shortOverVariation->cost_price;
            $shortoverProductSellingPrice = $shortOverVariation->selling_price;
            $shortoverProductFranchiseePrice = $shortOverVariation->franchisee_price;

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
                'product_franchisee_price' => $productFranchiseePrice,
                'shortover_product_variation_id' => $shortOverVariationId,
                'shortover_product_name' => $shortoverProductName,
                'shortover_product_size' => $shortoverProductSize,
                'shortover_product_metrics' => $shortoverProductMetrics,
                'shortover_product_cost_price' => $shortoverProductCostPrice,
                'shortover_product_selling_price' => $shortoverProductSellingPrice,
            ]);

        }

        return [
            'transaction' => $transaction,
            'items' => $transactionItems
        ];

    }

    public function voidAdjustmentShortTransaction($transactionId, $staffId, $userId = null)
    {
        $transaction = $this->transaction->find($transactionId);

        $transactionCode = $this->voidAdjustmentShort;

        $orNumber = $transaction->or_no;
        $customerUserId = $transaction->customer_user_id;
        $customerFirstname = $transaction->customer_firstname;
        $customerLastname = $transaction->customer_lastname;
        $customerId = $transaction->customer_id;
        $transactionUserId = $transaction->user_id;
        $branchId = $transaction->branch_id;

        $additionalTransactionData = [
            'or_no' => $orNumber,
            'customer_user_id' => $customerUserId,
            'customer_firstname' => $customerFirstname,
            'customer_lastname' => $customerLastname,
            'customer_id' => $customerId,
            'user_id' => $transactionUserId,
            'branch_id' => $branchId,
            'referenced_transaction_id' => $transactionId
        ];

        if(!$staffId){
            $transaction = $this->createAdminTransaction($transactionCode, $userId, $additionalTransactionData);
        }

        if($staffId){
            $transaction = $this->createStaffTransaction($transactionCode, $staffId, $additionalTransactionData);
        }

        if(!$transaction){
            return false;
        }

        $updated = $this->transaction->update($transactionId, [
            'status' => $this->voidFlag
        ]);

        $items = $this->transactionItem->getByTransactionId($transactionId);

        $voidTransactionId = $transaction->id;

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
                $currentQuantity = $itemsQuantitySnapshots[$variationId] - $quantity;
            }

            $remainingQuantity = $currentQuantity + $quantity;

            $transactionItems[] = $this->createTransactionItem($voidTransactionId, [
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

    public function voidAdjustmentShortOverTransaction($transactionId, $staffId, $userId = null)
    {
        $transaction = $this->transaction->find($transactionId);

        $transactionCode = $this->voidAdjustmentShortover;

        $orNumber = $transaction->or_no;
        $customerUserId = $transaction->customer_user_id;
        $customerFirstname = $transaction->customer_firstname;
        $customerLastname = $transaction->customer_lastname;
        $customerId = $transaction->customer_id;
        $transactionUserId = $transaction->user_id;
        $branchId = $transaction->branch_id;

        $additionalTransactionData = [
            'or_no' => $orNumber,
            'customer_user_id' => $customerUserId,
            'customer_firstname' => $customerFirstname,
            'customer_lastname' => $customerLastname,
            'customer_id' => $customerId,
            'user_id' => $transactionUserId,
            'branch_id' => $branchId,
            'referenced_transaction_id' => $transactionId
        ];

        if(!$staffId){
            $transaction = $this->createAdminTransaction($transactionCode, $userId, $additionalTransactionData);
        }

        if($staffId){
            $transaction = $this->createStaffTransaction($transactionCode, $staffId, $additionalTransactionData);
        }

        if(!$transaction){
            return false;
        }

        $updated = $this->transaction->update($transactionId, [
            'status' => $this->voidFlag
        ]);

        $items = $this->transactionItem->getByTransactionId($transactionId);

        $voidTransactionId = $transaction->id;

        $transactionItems = [];

        // get item current quantity snapshot by branch
        $itemsQuantitySnapshots = $this->getItemsCurrentQuantitySnapshotsByBranch($branchId, $items);

        foreach($items as $item){

            $variationId = $item['product_variation_id'];
            $shortOverVariationId = $item['shortover_product_variation_id'];
            $quantity = $item['quantity'];

            $variation = $this->variation->find($variationId);
            $shortOverVariation = $this->variation->find($shortOverVariationId);

            $productName = $variation->name;
            $productSize = $variation->size;
            $productMetrics = $variation->metrics;
            $productCostPrice = $variation->cost_price;
            $productSellingPrice = $variation->selling_price;
            $productFranchiseePrice = $variation->franchisee_price;

            $shortoverProductName = $shortOverVariation->name;
            $shortoverProductSize = $shortOverVariation->size;
            $shortoverProductMetrics = $shortOverVariation->metrics;
            $shortoverProductCostPrice = $shortOverVariation->cost_price;
            $shortoverProductSellingPrice = $shortOverVariation->selling_price;
            $shortoverProductFranchiseePrice = $shortOverVariation->franchisee_price;

            $currentQuantity = 0;

            if(isset($itemsQuantitySnapshots[$variationId])){
                $currentQuantity = $itemsQuantitySnapshots[$variationId] - $quantity;
            }

            $remainingQuantity = $currentQuantity + $quantity;

            $transactionItems[] = $this->createTransactionItem($voidTransactionId, [
                'product_variation_id' => $variationId,
                'current_quantity' => $currentQuantity,
                'quantity' => $quantity,
                'remaining_quantity' => $remainingQuantity,
                'product_name' => $productName,
                'product_size' => $productSize,
                'product_metrics' => $productMetrics,
                'product_cost_price' => $productCostPrice,
                'product_selling_price' => $productSellingPrice,
                'product_franchisee_price' => $productFranchiseePrice,
                'shortover_product_variation_id' => $shortOverVariationId,
                'shortover_product_name' => $shortoverProductName,
                'shortover_product_size' => $shortoverProductSize,
                'shortover_product_metrics' => $shortoverProductMetrics,
                'shortover_product_cost_price' => $shortoverProductCostPrice,
                'shortover_product_selling_price' => $shortoverProductSellingPrice,
            ]);

        }

        return [
            'transaction' => $transaction,
            'items' => $transactionItems
        ];


    }

}