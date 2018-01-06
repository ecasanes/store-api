<?php namespace App\DTIStore\Services;

use App\DTIStore\Helpers\StatusHelper;
use App\DTIStore\Repositories\StoreStockInterface;
use App\DTIStore\Repositories\CompanyStockInterface;
use App\DTIStore\Repositories\DeliveryInterface;
use App\DTIStore\Repositories\DeliveryItemInterface;
use App\DTIStore\Repositories\PriceRuleInterface;
use App\DTIStore\Repositories\ProductInterface;
use App\DTIStore\Repositories\ProductCategoryInterface;
use App\DTIStore\Repositories\ProductVariationInterface;
use App\DTIStore\Repositories\TransactionInterface;
use App\DTIStore\Repositories\TransactionTypeInterface;
use App\DTIStore\Services\Traits\Product\BranchStockTrait;
use App\DTIStore\Services\Traits\Product\CompanyStockTrait;
use App\DTIStore\Services\Traits\Product\DeliveryTrait;
use App\DTIStore\Services\Traits\Product\ProductCategoryTrait;
use App\DTIStore\Services\Traits\Product\ProductVariationTrait;
use App\DTIStore\Services\Traits\Transaction\SaleTransactionTrait;

class ProductService
{
    protected $product;
    protected $category;
    protected $variation;
    protected $companyStock;
    protected $branchStock;
    protected $transaction;
    protected $delivery;
    protected $deliveryItem;
    protected $priceRule;
    protected $transactionType;

    use ProductCategoryTrait;
    use ProductVariationTrait;
    use CompanyStockTrait;
    use BranchStockTrait;
    use DeliveryTrait;

    public function __construct(
        ProductInterface $product,
        ProductCategoryInterface $category,
        ProductVariationInterface $variation,
        CompanyStockInterface $companyStock,
        StoreStockInterface $branchStock,
        TransactionInterface $transaction,
        DeliveryInterface $delivery,
        DeliveryItemInterface $deliveryItem,
        PriceRuleInterface $priceRule,
        TransactionTypeInterface $transactionType
    )
    {
        $this->product = $product;
        $this->category = $category;
        $this->variation = $variation;
        $this->companyStock = $companyStock;
        $this->branchStock = $branchStock;
        $this->transaction = $transaction;
        $this->delivery = $delivery;
        $this->deliveryItem = $deliveryItem;
        $this->priceRule = $priceRule;
        $this->transactionType = $transactionType;
    }

    public function create(array $data)
    {
        $product = $this->product->create($data);

        return $product;
    }

    public function find($id)
    {
        $product = $this->product->find($id);

        if(!$product){
            return $product;
        }

        return $product;
    }

    public function getAll()
    {
        $products = $this->product->getAll();

        return $products;

    }

    public function update($id, $data)
    {
        $updated = $this->product->update($id, $data);

        return $updated;
    }

    public function delete($id)
    {
        $deleted = $this->product->delete($id);

        return $deleted;
    }

    public function isDeleted($id)
    {
        $isDeleted = $this->product->isDeleted($id);

        return $isDeleted;
    }

    public function filter($data)
    {
        $products = $this->product->filter($data);

        return $products;
    }

    public function getFilterMeta($data)
    {
        $meta = $this->product->getFilterMeta($data);

        return $meta;
    }

    public function updateProductVariationWithProductData($id, $data)
    {
        $updated = $this->variation->updateWithProductData($id, $data);

        return $updated;
    }

    public function hasEnoughStocksByValidation($itemValidations)
    {

        foreach($itemValidations as $itemValidation){
            $isValid = $itemValidation['valid'];
            if(!$isValid){
                return false;
            }
        }

        return true;
    }

    public function hasNoZeroValueStocksValidation($items)
    {

        $itemValidations = [];

        foreach($items as $delivery){

            $isValid = false;

            $quantity = $delivery['quantity'];

            if($quantity>0){
                $isValid = true;
            }

            $delivery['valid'] = $isValid;

            $itemValidations[] = $delivery;
        }

        return $itemValidations;
    }

    public function hasAtLeastOneZeroValueStocksValidation($items)
    {

        foreach($items as $delivery){

            $quantity = $delivery['quantity'];

            if($quantity>0){
                return true;
            }
        }

        return false;

    }

    public function getByTransactionId($transactionId, $data)
    {
        $products = $this->product->getByTransactionId($transactionId, $data);

        return $products;
    }

    public function getByTransactionIdMeta($transactionId, $data)
    {
        $meta = $this->product->getByTransactionIdMeta($transactionId, $data);

        return $meta;
    }

    public function filterProductCategories(array $filter)
    {
        $categories = $this->category->filter($filter);

        return $categories;
    }

    public function getProductCategoryMeta($data)
    {
        $meta = $this->category->getFilterMeta($data);

        return $meta;
    }

    public function getAlerts($filter, $threshold = StatusHelper::DEFAULT_LOW_THRESHOLD)
    {
        $alerts = $this->branchStock->getAlerts($filter, $threshold);

        return $alerts;
    }

    public function getAlertsByItems($items, $threshold = StatusHelper::DEFAULT_LOW_THRESHOLD)
    {
        $itemIds = [];

        foreach($items as $item){

            $productVariationId = $item['product_variation_id'];
            $itemIds[] = $productVariationId;

        }

        $alerts = $this->branchStock->getAlertsByItemIds($itemIds, $threshold);

        return $alerts;
    }

    public function getPriceRulesByType($ruleType)
    {
        $ruleTypes = $this->priceRule->getPriceRulesByType($ruleType);

        return $ruleTypes;
    }

    public function getAllPriceRules()
    {
        $priceRules = $this->priceRule->getAll();

        return $priceRules;
    }

    public function updateBranchStocksByTransactionDetails($branchId, $transactionTypeId, $transactionItems)
    {
        $transactionType = $this->transactionType->find($transactionTypeId);

        if(!$transactionType){
            return false;
        }

        $code = $transactionType->code;

        switch($code){
            case StatusHelper::SALE:
                $this->subtractBranchStocks($branchId, $transactionItems);
                $this->addSoldItemsCount($branchId, $transactionItems);
                break;
            case StatusHelper::VOID_SALE:
                $this->addBranchStocks($branchId, $transactionItems);
                $this->subtractSoldItemsCount($branchId, $transactionItems);
                break;
            case StatusHelper::RETURN_SALE:
                $this->addBranchStocks($branchId, $transactionItems);
                $this->subtractSoldItemsCount($branchId, $transactionItems);
                break;
            case StatusHelper::VOID_RETURN:
                $this->subtractBranchStocks($branchId, $transactionItems);
                $this->addSoldItemsCount($branchId, $transactionItems);
                break;
        }

        return true;

    }

}