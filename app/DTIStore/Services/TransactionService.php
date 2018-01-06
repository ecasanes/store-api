<?php namespace App\DTIStore\Services;

use App\DTIStore\Helpers\StatusHelper;
use App\DTIStore\Repositories\StoreInterface;
use App\DTIStore\Repositories\StoreStockInterface;
use App\DTIStore\Repositories\ProductInterface;
use App\DTIStore\Repositories\ProductVariationInterface;
use App\DTIStore\Repositories\TransactionInterface;
use App\DTIStore\Repositories\TransactionItemInterface;
use App\DTIStore\Repositories\TransactionTypeInterface;
use App\DTIStore\Repositories\UserInterface;
use App\DTIStore\Services\Traits\Transaction\BranchStockTransactionsTrait;
use App\DTIStore\Services\Traits\Transaction\CompanyStockTransactionsTrait;
use App\DTIStore\Services\Traits\Transaction\DeliverySaleTransactionTrait;
use App\DTIStore\Services\Traits\Transaction\DeliveryTransactionsTrait;
use App\DTIStore\Services\Traits\Transaction\FranchiseeSaleTransactionTrait;
use App\DTIStore\Services\Traits\Transaction\ReportTrait;
use App\DTIStore\Services\Traits\Transaction\SaleTransactionTrait;

class TransactionService
{
    protected $product;
    protected $user;
    protected $variation;
    protected $transaction;
    protected $transactionItem;
    protected $transactionType;
    protected $branch;
    protected $branchStock;

    protected $addStockCode = StatusHelper::ADD_STOCK;
    protected $subStockCode = StatusHelper::SUB_STOCK;
    protected $sale = StatusHelper::SALE;
    protected $void = StatusHelper::VOID_SALE;
    protected $returnSale = StatusHelper::RETURN_SALE;


    protected $voidFlag = StatusHelper::VOID;

    public function __construct(
        ProductInterface $product,
        ProductVariationInterface $variation,
        TransactionInterface $transaction,
        TransactionItemInterface $transactionItem,
        TransactionTypeInterface $transactionType,
        StoreInterface $branch,
        StoreStockInterface $branchStock,
        UserInterface $user
    )
    {
        $this->product = $product;
        $this->variation = $variation;
        $this->transaction = $transaction;
        $this->transactionItem = $transactionItem;
        $this->transactionType = $transactionType;
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

    public function getByOr($orNumber, $adjustmentShortOverCode, $branchId = null)
    {
        $transactions = $this->transaction->getByOr($orNumber, $adjustmentShortOverCode, $branchId);

        return $transactions;
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

        $discount = null;


        return $this->transaction->create([
                'transaction_type_id' => $transactionTypeId,
                'staff_id' => $staffId,
                'staff_firstname' => $staffFirstName,
                'staff_lastname' => $staffLastName,
                'staff_phone' => $staffPhone,
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

}