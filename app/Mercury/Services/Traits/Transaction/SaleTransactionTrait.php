<?php namespace App\Mercury\Services\Traits\Transaction;

use App\Mercury\Helpers\StatusHelper;

trait SaleTransactionTrait{

    public function createSaleTransaction($branchId, $staffId, $orNumber, $items, $priceRuleId = null)
    {

        $saleTransactionCode = $this->sale;
        $subType = null;
        $branchType = null;
        $priceRuleData = [];

        $isFranchisee = $this->isBranchFranchisee($branchId);

        if($isFranchisee){
            $subType = StatusHelper::FRANCHISEE;
            $branchType = StatusHelper::FRANCHISEE;
        }

        $priceRule = $this->priceRule->find($priceRuleId);

        if($priceRule){
            $priceRuleData = [
                'price_rule_id' => $priceRuleId,
                'price_rule_name' => $priceRule->name,
                'price_rule_code' => $priceRule->code,
                'discount_type' => $priceRule->discount_type,
                'discount_value' => $priceRule->discount,
                'discount_apply_to' => $priceRule->apply_to,
                'discount_product_variation_id' => $priceRule->product_variation_id,
                'discount_amount' => $priceRule->amount,
                'discount_quantity' => $priceRule->quantity
            ];
        }

        $transaction = $this->createStaffTransaction($saleTransactionCode, $staffId, [
            'or_no' => $orNumber,
            'branch_id' => $branchId,
            'sub_type' => $subType,
            'branch_type' => $branchType
        ] + $priceRuleData );

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

    public function createReturnSaleTransaction($existingSaleTransactionId, $branchId, $staffId, $orNumber, $items)
    {

        $returnSaleTransactionCode = $this->returnSale;
        $subType = null;
        $branchType = null;

        $isFranchisee = $this->isBranchFranchisee($branchId);

        if($isFranchisee){
            $subType = StatusHelper::FRANCHISEE;
            $branchType = StatusHelper::FRANCHISEE;
        }

        $transaction = $this->createStaffTransaction($returnSaleTransactionCode, $staffId, [
            'or_no' => $orNumber,
            'branch_id' => $branchId,
            'referenced_transaction_id' => $existingSaleTransactionId,
            'branch_type' => $branchType,
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

    public function createShortSaleTransaction($existingSaleTransactionId, $branchId, $staffId, $orNumber, $items)
    {

        $returnSaleTransactionCode = $this->shortSale;
        $subType = null;
        $branchType = null;

        $isFranchisee = $this->isBranchFranchisee($branchId);

        if($isFranchisee){
            $subType = StatusHelper::FRANCHISEE;
            $branchType = StatusHelper::FRANCHISEE;
        }

        $transaction = $this->createStaffTransaction($returnSaleTransactionCode, $staffId, [
            'or_no' => $orNumber,
            'branch_id' => $branchId,
            'referenced_transaction_id' => $existingSaleTransactionId,
            'branch_type' => $branchType,
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

    public function createShortoverSaleTransaction($existingSaleTransactionId, $branchId, $staffId, $orNumber, $items)
    {

        $returnSaleTransactionCode = $this->shortoverSale;
        $subType = null;
        $branchType = null;

        $isFranchisee = $this->isBranchFranchisee($branchId);

        if($isFranchisee){
            $subType = StatusHelper::FRANCHISEE;
            $branchType = StatusHelper::FRANCHISEE;
        }

        $transaction = $this->createStaffTransaction($returnSaleTransactionCode, $staffId, [
            'or_no' => $orNumber,
            'branch_id' => $branchId,
            'referenced_transaction_id' => $existingSaleTransactionId,
            'branch_type' => $branchType,
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

    public function voidSaleTransaction($transactionId, $staffId, $userId = null)
    {
        $transaction = $this->transaction->find($transactionId);

        $transactionCode = StatusHelper::VOID_SALE;
        $subType = null;
        $branchType = null;

        $branchId = $transaction->branch_id;
        $isFranchisee = $this->isBranchFranchisee($branchId);

        if($isFranchisee){
            $subType = StatusHelper::FRANCHISEE;
            $branchType = StatusHelper::FRANCHISEE;
        }

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
            'referenced_transaction_id' => $transactionId,
            'branch_type' => $branchType,
            'sub_type' => $subType
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

    public function voidReturnSaleTransaction($transactionId, $staffId, $userId = null)
    {
        $transaction = $this->transaction->find($transactionId);

        $transactionCode = $this->voidReturnSale;
        $subType = null;
        $branchType = null;

        $branchId = $transaction->branch_id;
        $isFranchisee = $this->isBranchFranchisee($branchId);

        if($isFranchisee){
            $subType = StatusHelper::FRANCHISEE;
            $branchType = StatusHelper::FRANCHISEE;
        }

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
            'referenced_transaction_id' => $transactionId,
            'branch_type' => $branchType,
            'sub_type' => $subType
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
                $currentQuantity = $itemsQuantitySnapshots[$variationId] + $quantity;
            }

            $remainingQuantity = $currentQuantity - $quantity;

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

    public function voidShortSaleTransaction($transactionId, $staffId, $userId = null)
    {
        $transaction = $this->transaction->find($transactionId);

        $transactionCode = $this->voidShortSale;
        $subType = null;
        $branchType = null;

        $branchId = $transaction->branch_id;
        $isFranchisee = $this->isBranchFranchisee($branchId);

        if($isFranchisee){
            $subType = StatusHelper::FRANCHISEE;
            $branchType = StatusHelper::FRANCHISEE;
        }

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
            'referenced_transaction_id' => $transactionId,
            'branch_type' => $branchType,
            'sub_type' => $subType
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
                $currentQuantity = $itemsQuantitySnapshots[$variationId] + $quantity;
            }

            $remainingQuantity = $currentQuantity - $quantity;

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

    public function voidShortoverSaleTransaction($transactionId, $staffId, $userId = null)
    {
        $transaction = $this->transaction->find($transactionId);

        $transactionCode = $this->voidShortoverSale;
        $subType = null;
        $branchType = null;

        $branchId = $transaction->branch_id;
        $isFranchisee = $this->isBranchFranchisee($branchId);

        if($isFranchisee){
            $subType = StatusHelper::FRANCHISEE;
            $branchType = StatusHelper::FRANCHISEE;
        }

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
            'referenced_transaction_id' => $transactionId,
            'branch_type' => $branchType,
            'sub_type' => $subType
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
                $currentQuantity = $itemsQuantitySnapshots[$variationId] + $quantity;
            }

            $remainingQuantity = $currentQuantity - $quantity;

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

}