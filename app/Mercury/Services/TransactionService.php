<?php namespace App\Mercury\Services;

use App\Mercury\Helpers\StatusHelper;
use App\Mercury\Repositories\BranchInterface;
use App\Mercury\Repositories\BranchStaffInterface;
use App\Mercury\Repositories\BranchStockInterface;
use App\Mercury\Repositories\CompanyInterface;
use App\Mercury\Repositories\CompanyStockInterface;
use App\Mercury\Repositories\PriceRuleInterface;
use App\Mercury\Repositories\ProductInterface;
use App\Mercury\Repositories\ProductVariationInterface;
use App\Mercury\Repositories\TransactionInterface;
use App\Mercury\Repositories\TransactionItemInterface;
use App\Mercury\Repositories\TransactionTypeInterface;
use App\Mercury\Repositories\UserInterface;
use App\Mercury\Services\Traits\Transaction\BranchStockTransactionsTrait;
use App\Mercury\Services\Traits\Transaction\CompanyStockTransactionsTrait;
use App\Mercury\Services\Traits\Transaction\DeliverySaleTransactionTrait;
use App\Mercury\Services\Traits\Transaction\DeliveryTransactionsTrait;
use App\Mercury\Services\Traits\Transaction\FranchiseeSaleTransactionTrait;
use App\Mercury\Services\Traits\Transaction\ReportTrait;
use App\Mercury\Services\Traits\Transaction\SaleTransactionTrait;

class TransactionService
{
    protected $product;
    protected $user;
    protected $variation;
    protected $transaction;
    protected $transactionItem;
    protected $transactionType;
    protected $companyStock;
    protected $branchStaff;
    protected $priceRule;
    protected $branch;
    protected $branchStock;

    protected $addStockCode = StatusHelper::ADD_STOCK;
    protected $subStockCode = StatusHelper::SUB_STOCK;
    protected $deliverStockCode = StatusHelper::DELIVER_STOCK;
    protected $returnStockCode = StatusHelper::RETURN_STOCK;
    protected $sale = StatusHelper::SALE;
    protected $deliverySale = StatusHelper::DELIVERY_SALE;
    protected $franciseeSale = StatusHelper::FRANCHISEE_SALE;
    protected $void = StatusHelper::VOID_SALE;
    protected $voidDeliverySale = StatusHelper::VOID_DELIVERY_SALE;
    protected $voidFranchiseeSale = StatusHelper::VOID_FRANCHISEE_SALE;
    protected $returnSale = StatusHelper::RETURN_SALE;
    protected $returnDeliverySale = StatusHelper::RETURN_DELIVERY_SALE;
    protected $shortSale = StatusHelper::SHORT_SALE;
    protected $shortoverSale = StatusHelper::SHORTOVER_SALE;
    protected $voidReturnSale = StatusHelper::VOID_RETURN;
    protected $voidShortSale = StatusHelper::VOID_SHORT;
    protected $voidShortoverSale = StatusHelper::VOID_SHORTOVER;

    protected $adjustmentShort = StatusHelper::ADJUSTMENT_SHORT;
    protected $adjustmentShortover = StatusHelper::ADJUSTMENT_SHORTOVER;
    protected $voidAdjustmentShort = StatusHelper::VOID_ADJUSTMENT_SHORT;
    protected $voidAdjustmentShortover = StatusHelper::VOID_ADJUSTMENT_SHORTOVER;

    protected $voidFlag = StatusHelper::VOID;

    public function __construct(
        ProductInterface $product,
        ProductVariationInterface $variation,
        TransactionInterface $transaction,
        TransactionItemInterface $transactionItem,
        TransactionTypeInterface $transactionType,
        CompanyStockInterface $companyStock,
        BranchStaffInterface $branchStaff,
        PriceRuleInterface $priceRule,
        BranchInterface $branch,
        BranchStockInterface $branchStock,
        UserInterface $user
    )
    {
        $this->product = $product;
        $this->variation = $variation;
        $this->transaction = $transaction;
        $this->transactionItem = $transactionItem;
        $this->transactionType = $transactionType;
        $this->companyStock = $companyStock;
        $this->branchStaff = $branchStaff;
        $this->priceRule = $priceRule;
        $this->branch = $branch;
        $this->branchStock = $branchStock;
        $this->user = $user;
    }

    use DeliveryTransactionsTrait;
    use CompanyStockTransactionsTrait;
    use BranchStockTransactionsTrait;
    use SaleTransactionTrait;
    use DeliverySaleTransactionTrait;
    use FranchiseeSaleTransactionTrait;
    use ReportTrait;


    public function create($data)
    {
        $transaction = $this->transaction->create($data);

        return $transaction;
    }

    public function update($transactionId, array $data)
    {
        $updated = $this->transaction->update($transactionId, $data);

        return $updated;
    }

    public function filter($data)
    {
        $products = $this->transaction->filter($data);

        return $products;
    }

    public function getFilterMeta($data)
    {
        $meta = $this->transaction->getFilterMeta($data);

        return $meta;
    }

    public function transactionTypesFilter($data)
    {
        $transactionTypes = $this->transactionType->filter($data);

        return $transactionTypes;
    }

    public function find($transactionId)
    {
        $transaction = $this->transaction->find($transactionId);

        return $transaction;
    }

    public function getTransactionItemsByTransactionId($transactionId)
    {
        $items = $this->transactionItem->getByTransactionId($transactionId);

        return $items;
    }

    public function findByOr($orNo, $transactionTypeCode = StatusHelper::SALE, $includeVoid = false, $branchId = null)
    {
        $transaction = $this->transaction->findByOr($orNo, $transactionTypeCode, $includeVoid, $branchId);

        return $transaction;
    }

    public function findReturnSaleByOr($orNo, $branchId = null)
    {
        $transaction = $this->transaction->findReturnSaleByOr($orNo, $branchId);

        return $transaction;
    }

    public function validateEnoughReturnStocks($existingSaleTransactionItems, $currentReturnItems)
    {

        $itemValidations = [];

        foreach ($currentReturnItems as $item) {

            $productVariationId = $item['product_variation_id'];
            $quantity = $item['quantity'];

            $item['valid'] = false;

            foreach ($existingSaleTransactionItems as $existingItem) {

                $existingProductVariationId = $existingItem['product_variation_id'];
                $existingQuantity = $existingItem['quantity'];

                if ($item['valid']) {
                    continue;
                }

                if ($existingProductVariationId != $productVariationId) {
                    continue;
                }

                // quantity = 0 is still valid but don't affect returns
                if ($quantity <= $existingQuantity) {
                    $item['valid'] = true;
                }

            }

            $itemValidations[] = $item;

        }

        return $itemValidations;
    }

    public function validateEnoughShortOverItemQuantityByOr($orNumber, $shortOverItems, $branchId = null)
    {
        $itemValidations = [];
        $adjustmentShortOverCode = StatusHelper::ADJUSTMENT_SHORTOVER;
        $saleCode = StatusHelper::SALE;

        $existingShortOverItems = $this->transactionItem->getByOr($orNumber, $adjustmentShortOverCode, $branchId);
        $existingSaleItems = $this->transactionItem->getByOr($orNumber, $saleCode, $branchId);

        foreach ($shortOverItems as $item) {

            $item['valid'] = true;

            $itemId = $item['product_variation_id'];
            $itemQuantity = $item['quantity'];

            $totalSaleItemQuantity = 0;
            $totalExistingShortOverQuantity = 0;

            foreach ($existingSaleItems as $saleItem) {

                $existingSaleProductVariationId = $saleItem->product_variation_id;
                $existingSaleItemQuantity = $saleItem->quantity;

                if ($existingSaleProductVariationId == $itemId) {
                    $totalSaleItemQuantity += $existingSaleItemQuantity;
                }

            }

            foreach ($existingShortOverItems as $existingItem) {

                $existingProductVariationId = $existingItem->product_variation_id;
                $existingItemQuantity = $existingItem->quantity;

                if ($existingProductVariationId == $itemId) {
                    $totalExistingShortOverQuantity += $existingItemQuantity;
                }


            }


            $remainingQuantityAllowedToShortOver = $totalSaleItemQuantity - $totalExistingShortOverQuantity;

            if ($itemQuantity > $remainingQuantityAllowedToShortOver) {
                //$item['shortover_quantity'] = $totalExistingShortOverQuantity;
                //$item['item_quantity'] = $itemQuantity;
                $item['valid'] = false;
            }

            $itemValidations[] = $item;

        }

        return $itemValidations;

    }

    public function getByOr($orNumber, $adjustmentShortOverCode, $branchId = null)
    {
        $transactions = $this->transaction->getByOr($orNumber, $adjustmentShortOverCode, $branchId);

        return $transactions;
    }

    public function validateShortOverOfSameId($items)
    {
        $itemValidations = [];

        foreach ($items as $item) {

            $item['valid'] = true;

            $productVariationId = $item['product_variation_id'];
            $shortOverProductVariationId = $item['shortover_product_variation_id'];

            if ($productVariationId == $shortOverProductVariationId) {
                $item['valid'] = false;
            }

            $itemValidations[] = $item;

        }

        return $itemValidations;
    }

    public function calculateDiscountByItems(array $items, $customerType = StatusHelper::GUEST, $priceRuleId = null)
    {

        $buyXDiscount = StatusHelper::PRICE_RULE_BUYX;
        $simpleDiscount = StatusHelper::PRICE_RULE_SIMPLE;
        $spendXDiscount = StatusHelper::PRICE_RULE_SPENDX;
        $noDiscount = StatusHelper::PRICE_RULE_NO_DISCOUNT;
        $specialDiscount = StatusHelper::PRICE_RULE_SPECIAL;

        $fixDiscountType = StatusHelper::DISCOUNT_TYPE_FIX;
        $percentDiscountType = StatusHelper::DISCOUNT_TYPE_PERCENT;

        $currentPriceRuleType = null;

        if ($priceRuleId) {

            $priceRule = $this->priceRule->find($priceRuleId);
            $currentPriceRuleType = $priceRule->type;

        }

        $discounts = [0];

        $availablePriceRules = $this->priceRule->getPriceRulesByApplication($customerType);

        $total = $this->calculateTotal($items, $availablePriceRules);

        //dump($total);

        foreach ($items as $item) {

            if (!isset($item['quantity']) || !isset($item['product_variation_id'])) {
                continue;
            }

            $quantity = $item['quantity'];
            $itemId = $item['product_variation_id'];

            foreach ($availablePriceRules as $priceRule) {

                $priceRuleId = $priceRule['id'];
                $priceRuleType = $priceRule['type'];
                $discountType = $priceRule['discount_type'];
                $discountItemId = $priceRule['product_variation_id'];
                $applicableDiscountQuantity = ['quantity'];
                $discount = $priceRule['discount'];

                if ($itemId != $discountItemId) {
                    continue;
                }

                if ($quantity < $applicableDiscountQuantity) {
                    continue;
                }

                if ($priceRuleType == $specialDiscount && !$currentPriceRuleType) {
                    continue;
                }

                if ($currentPriceRuleType == $specialDiscount && $discountType == $fixDiscountType) {
                    $discounts[] = $discount;
                    continue;
                }

                if ($currentPriceRuleType == $specialDiscount && $discountType == $percentDiscountType) {
                    $discounts[] = $total * ($discount / 100);
                    continue;
                }

                if ($priceRuleType == $buyXDiscount && $discountType == $fixDiscountType) {
                    //dump('buy '.$applicableDiscountQuantity.' discount '.$discount.' PHP');

                    $discounts[] = $discount;
                    continue;

                }

                if ($priceRuleType == $buyXDiscount && $discountType == $percentDiscountType) {
                    //dump('buy '.$applicableDiscountQuantity.' discount '.$discount.'%');

                    $discounts[] = $total * ($discount / 100);
                    continue;

                }

            }
        }

        //dump($discounts);

        foreach ($availablePriceRules as $priceRule) {

            $priceRuleId = $priceRule['id'];
            $priceRuleType = $priceRule['type'];
            $discount = $priceRule['discount'];
            $discountItemId = $priceRule['product_variation_id'];
            $discountType = $priceRule['discount_type'];
            $amountToSpend = $priceRule['amount'];

            //dump('discount item id');
            //dump($discountItemId);


            $itemIsDiscounted = true;

            foreach ($items as $item) {

                $itemId = $item['product_variation_id'];

                if ($itemId != $discountItemId) {
                    $itemIsDiscounted = false;
                }

            }

            if (!$itemIsDiscounted && !empty($discountItemId)) {
                continue;
            }

            if ($priceRuleType == $specialDiscount && !$currentPriceRuleType) {
                continue;
            }


            if ($currentPriceRuleType == $specialDiscount && $discountType == $fixDiscountType) {
                $discounts[] = $discount;
            }

            if ($currentPriceRuleType == $specialDiscount && $discountType == $percentDiscountType) {
                $discounts[] = $total * ($discount / 100);
            }

            if ($priceRuleType == $simpleDiscount && $discountType == $fixDiscountType) {
                //dump('simple discount '.$discount.' PHP');
                $discounts[] = $discount;

            }

            if ($priceRuleType == $simpleDiscount && $discountType == $percentDiscountType) {
                //dump('simple discount '.$discount.'%');
                $discounts[] = $total * ($discount / 100);

            }

            if ($priceRuleType != $spendXDiscount) {
                continue;
            }

            if ($total < $amountToSpend) {
                continue;
            }

            if ($discountType == $fixDiscountType) {
                $discounts[] = $discount;
                continue;
            }

            if ($discountType == $percentDiscountType) {
                $discounts[] = $total * ($discount / 100);
                continue;
            }

        }

        //dump($discounts);

        $discounts = array_unique($discounts);

        $maxDiscount = max($discounts);

        return $maxDiscount;

    }

    public function calculateDiscountBySingleItem($productVariationId, $sellingPrice, $quantity, $customerType = StatusHelper::GUEST, $priceRuleId = null)
    {

        $buyXDiscount = StatusHelper::PRICE_RULE_BUYX;
        $simpleDiscount = StatusHelper::PRICE_RULE_SIMPLE;
        $spendXDiscount = StatusHelper::PRICE_RULE_SPENDX;
        $noDiscount = StatusHelper::PRICE_RULE_NO_DISCOUNT;
        $specialDiscount = StatusHelper::PRICE_RULE_SPECIAL;

        $fixDiscountType = StatusHelper::DISCOUNT_TYPE_FIX;
        $percentDiscountType = StatusHelper::DISCOUNT_TYPE_PERCENT;

        $currentPriceRuleType = null;

        if ($priceRuleId) {

            $priceRule = $this->priceRule->find($priceRuleId);
            $currentPriceRuleType = $priceRule->type;

        }

        $discounts = [0];

        $availablePriceRules = $this->priceRule->getPriceRulesByApplication($customerType);

        $total = $sellingPrice * $quantity;

        //dump($total);


        foreach ($availablePriceRules as $priceRule) {

            $priceRuleId = $priceRule['id'];
            $priceRuleType = $priceRule['type'];
            $discountType = $priceRule['discount_type'];
            $discountItemId = $priceRule['product_variation_id'];
            $applicableDiscountQuantity = ['quantity'];
            $discount = $priceRule['discount'];

            if ($productVariationId != $discountItemId) {
                continue;
            }

            if ($quantity < $applicableDiscountQuantity) {
                continue;
            }

            if ($priceRuleType == $specialDiscount && !$currentPriceRuleType) {
                continue;
            }

            if ($currentPriceRuleType == $specialDiscount && $discountType == $fixDiscountType) {
                $discounts[] = $discount;
                continue;
            }

            if ($currentPriceRuleType == $specialDiscount && $discountType == $percentDiscountType) {
                $discounts[] = $total * ($discount / 100);
                continue;
            }

            if ($priceRuleType == $buyXDiscount && $discountType == $fixDiscountType) {
                //dump('buy '.$applicableDiscountQuantity.' discount '.$discount.' PHP');

                $discounts[] = $discount;
                continue;

            }

            if ($priceRuleType == $buyXDiscount && $discountType == $percentDiscountType) {
                //dump('buy '.$applicableDiscountQuantity.' discount '.$discount.'%');

                $discounts[] = $total * ($discount / 100);
                continue;

            }

        }


        //dump($discounts);

        foreach ($availablePriceRules as $priceRule) {

            $priceRuleId = $priceRule['id'];
            $priceRuleType = $priceRule['type'];
            $discount = $priceRule['discount'];
            $discountItemId = $priceRule['product_variation_id'];
            $discountType = $priceRule['discount_type'];
            $amountToSpend = $priceRule['amount'];

            //dump('discount item id');
            //dump($discountItemId);


            $itemIsDiscounted = true;

            if ($productVariationId != $discountItemId) {
                $itemIsDiscounted = false;
            }


            if (!$itemIsDiscounted && !empty($discountItemId)) {
                continue;
            }

            if ($priceRuleType == $specialDiscount && !$currentPriceRuleType) {
                continue;
            }

            if ($currentPriceRuleType == $specialDiscount && $discountType == $fixDiscountType) {
                $discounts[] = $discount;
            }

            if ($currentPriceRuleType == $specialDiscount && $discountType == $percentDiscountType) {
                $discounts[] = $total * ($discount / 100);
            }


            if ($priceRuleType == $simpleDiscount && $discountType == $fixDiscountType) {
                //dump('simple discount '.$discount.' PHP');
                $discounts[] = $discount;

            }

            if ($priceRuleType == $simpleDiscount && $discountType == $percentDiscountType) {
                //dump('simple discount '.$discount.'%');
                $discounts[] = $total * ($discount / 100);

            }

            if ($priceRuleType != $spendXDiscount) {
                continue;
            }

            if ($total < $amountToSpend) {
                continue;
            }

            if ($discountType == $fixDiscountType) {
                $discounts[] = $discount;
                continue;
            }

            if ($discountType == $percentDiscountType) {
                $discounts[] = $total * ($discount / 100);
                continue;
            }

        }

        //dump($discounts);

        $discounts = array_unique($discounts);

        $maxDiscount = max($discounts);

        return $maxDiscount;

    }

    public function updateTransactionItemsDiscount($transactionId, $customerType = StatusHelper::GUEST)
    {
        $transactionItems = $this->transactionItem->getByTransactionId($transactionId);

        foreach ($transactionItems as $item) {

            $transactionItemId = $item->id;
            $productVariationId = $item->product_variation_id;
            $sellingPrice = $item->selling_price;
            $quantity = $item->quantity;

            $discount = $this->calculateDiscountBySingleItem($productVariationId, $sellingPrice, $quantity, $customerType);

            $updated = $this->transactionItem->update($transactionItemId, [
                'product_discount' => $discount
            ]);

        }

    }

    public function calculateTotal(array $items, array $availablePriceRules)
    {
        $noDiscount = StatusHelper::PRICE_RULE_NO_DISCOUNT;

        $total = 0;
        $noDiscountItems = [];

        foreach ($availablePriceRules as $priceRule) {

            $priceRuleType = $priceRule['type'];
            $itemId = $priceRule['product_variation_id'];

            if ($priceRuleType == $noDiscount) {
                $noDiscountItems[] = $itemId;
            }

        }

        foreach ($items as $item) {

            $quantity = $item['quantity'];
            $productVariationId = $item['product_variation_id'];
            $sellingPrice = 0;

            if (in_array($productVariationId, $noDiscountItems)) {
                continue;
            }

            if (isset($item['product_selling_price'])) {
                $sellingPrice = $item['product_selling_price'];
            }

            if (isset($item['selling_price'])) {
                $sellingPrice = $item['selling_price'];
            }

            $subtotal = $quantity * $sellingPrice;

            $total += $subtotal;
        }

        return $total;
    }

    public function getBranchesSalesSummary($filter)
    {
        $sales = $this->transaction->getBranchesSalesSummary($filter);

        return $sales;
    }

    public function getWarehouseLedger($filter)
    {
        $ledger = $this->transaction->getWarehouseLedger($filter);

        return $ledger;

    }

    public function getWarehouseLedgerMeta($filter)
    {
        $ledgerMeta = $this->transaction->getWarehouseLedgerMeta($filter);

        return $ledgerMeta;
    }

    public function getBranchLedger($filter)
    {
        $ledger = $this->transaction->getBranchLedger($filter);

        return $ledger;

    }

    public function getBranchLedgerMeta($filter)
    {
        $ledgerMeta = $this->transaction->getBranchLedgerMeta($filter);

        return $ledgerMeta;
    }

    public function getAllOrNumbersByBranchId($id)
    {

        $orNumbers = $this->transaction->getAllOrNumbersByBranchId($id);

        return $orNumbers;
    }

    public function createMultipleTransactionItems($transactionItemsData, $transactionId = null)
    {
        $transactionItems = [];

        foreach ($transactionItemsData as $transactionItemData) {

            if ($transactionId) {
                $transactionItemData['transaction_id'] = $transactionId;
            }

            $transactionItem = $this->transactionItem->create($transactionItemData);

            if ($transactionItem) {
                $transactionItems[] = $transactionItem;
            }

        }

        return $transactionItems;

    }

    public function updateTransactionStatusByORAndType($orNumber, $transactionTypeId, $branchId)
    {
        $transactionType = $this->transactionType->find($transactionTypeId);

        if (!$transactionType) {
            return true;
        }

        $transactionTypeCode = $transactionType->code;

        $transaction = null;

        switch ($transactionTypeCode) {
            case StatusHelper::VOID_SALE:
                $transaction = $this->findByOr($orNumber, StatusHelper::SALE, false, $branchId);
                break;
            case StatusHelper::VOID_RETURN:
                $transaction = $this->findByOr($orNumber, StatusHelper::RETURN_SALE, false, $branchId);
                break;
        }

        if(!$transaction){
            return true;
        }

        $transactionId = $transaction->id;

        $updated = $this->update($transactionId, [
            'status' => StatusHelper::VOID
        ]);

        return $updated;

    }


    private function createAdminTransaction($transactionCode, $userId, $additionalData = [])
    {
        $transactionType = $this->transactionType->findByCode($transactionCode);

        if (!$transactionType) {
            return false;
        }

        $staffFirstName = null;
        $staffLastName = null;

        $user = $this->user->find($userId);

        if ($user) {
            $staffFirstName = $user->firstname;
            $staffLastName = $user->lastname;
        }

        $transactionTypeId = $transactionType->id;

        return $this->transaction->create([
                'transaction_type_id' => $transactionTypeId,
                'user_id' => $userId,
                'staff_firstname' => $staffFirstName,
                'staff_lastname' => $staffLastName
            ] + $additionalData);
    }

    private function createStaffTransaction($transactionCode, $staffId, $additionalData = [])
    {
        $transactionType = $this->transactionType->findByCode($transactionCode);

        if (!$transactionType) {
            return false;
        }

        $transactionTypeId = $transactionType->id;

        $staff = $this->branchStaff->findByStaffId($staffId);

        if (!$staff) {
            return false;
        }

        $userId = $staff->user_id;
        $staffFirstName = null;
        $staffLastName = null;
        $staffPhone = null;

        $user = $this->user->find($userId);

        if ($user) {
            $staffFirstName = $user->firstname;
            $staffLastName = $user->lastname;
            $staffPhone = $user->phone;
        }

        $priceRuleId = null;
        $priceRuleCode = null;
        $discount = null;

        if (isset($additionalData['price_rule_id'])) {
            // TODO: do something that will add price rule code and discount
        }

        return $this->transaction->create([
                'transaction_type_id' => $transactionTypeId,
                'staff_id' => $staffId,
                'staff_firstname' => $staffFirstName,
                'staff_lastname' => $staffLastName,
                'staff_phone' => $staffPhone,
                'price_rule_code' => $priceRuleCode,
                'discount' => $discount
            ] + $additionalData);
    }

    private function createTransactionItem($transactionId, array $data)
    {
        $transactionItem = $this->transactionItem->create([
                'transaction_id' => $transactionId,
            ] + $data);

        return $transactionItem;
    }

    private function isBranchFranchisee($branchId)
    {
        $branch = $this->branch->find($branchId);

        if (!$branch) {
            return false;
        }

        $type = $branch->type;

        if ($type != StatusHelper::FRANCHISEE) {
            return false;
        }

        return true;
    }

    private function getItemsCurrentQuantitySnapshotsByBranch($branchId, $items)
    {

        $itemIds = [];

        foreach ($items as $item) {

            $item = json_decode(json_encode($item), true);

            if (isset($item['product_variation_id'])) {
                $itemIds[] = $item['product_variation_id'];
                continue;
            }

            if (isset($item['id'])) {
                $itemIds[] = $item['id'];
                continue;
            }

        }

        $branchStocks = $this->branchStock->getBranchStocksByItemIds($branchId, $itemIds);

        $itemsQuantitySnapshots = [];

        foreach ($branchStocks as $branchStock) {

            $productVariationId = $branchStock->product_variation_id;
            $currentQuantity = $branchStock->quantity;

            $itemsQuantitySnapshots[$productVariationId] = $currentQuantity;

        }

        return $itemsQuantitySnapshots;

    }

    private function getItemsCurrentWarehouseQuantitySnapshots($items, $companyId = null)
    {
        $itemIds = [];

        foreach ($items as $item) {

            $item = json_decode(json_encode($item), true);

            if (isset($item['product_variation_id'])) {
                $itemIds[] = $item['product_variation_id'];
                continue;
            }

            if (isset($item['id'])) {
                $itemIds[] = $item['id'];
                continue;
            }
        }

        $companyStocks = $this->companyStock->getCompanyStocksByItemIds($itemIds, $companyId);

        $itemsQuantitySnapshots = [];

        foreach ($companyStocks as $companyStock) {

            $productVariationId = $companyStock->product_variation_id;
            $currentQuantity = $companyStock->quantity;

            $itemsQuantitySnapshots[$productVariationId] = $currentQuantity;

        }

        return $itemsQuantitySnapshots;
    }

}