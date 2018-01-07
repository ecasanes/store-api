<?php namespace App\DTIStore\Services;

use App\DTIStore\Helpers\StatusHelper;
use App\DTIStore\Repositories\StoreStockInterface;
use App\DTIStore\Repositories\ProductInterface;
use App\DTIStore\Repositories\ProductCategoryInterface;
use App\DTIStore\Repositories\ProductVariationInterface;
use App\DTIStore\Repositories\TransactionInterface;
use App\DTIStore\Repositories\TransactionTypeInterface;
use App\DTIStore\Services\Traits\Product\StoreStockTrait;
use App\DTIStore\Services\Traits\Product\CompanyStockTrait;
use App\DTIStore\Services\Traits\Product\DeliveryTrait;
use App\DTIStore\Services\Traits\Product\ProductCategoryTrait;
use App\DTIStore\Services\Traits\Product\ProductVariationTrait;
use App\ProductCondition;

class ProductService
{
    protected $product;
    protected $category;
    protected $variation;
    protected $branchStock;
    protected $transaction;
    protected $transactionType;

    use ProductCategoryTrait;
    use ProductVariationTrait;
    use CompanyStockTrait;
    use StoreStockTrait;
    use DeliveryTrait;

    public function __construct(
        ProductInterface $product,
        ProductCategoryInterface $category,
        ProductVariationInterface $variation,
        StoreStockInterface $branchStock,
        TransactionInterface $transaction,
        TransactionTypeInterface $transactionType
    )
    {
        $this->product = $product;
        $this->category = $category;
        $this->variation = $variation;
        $this->branchStock = $branchStock;
        $this->transaction = $transaction;
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

    public function getAllConditions()
    {
        return ProductCondition::all();
    }

    public function addToCart($productVariationId, $userId, $quantity)
    {
        return $this->product->addToCart($productVariationId, $userId, $quantity);
    }

    public function removeInCart($productVariationId, $userId)
    {
        return $this->product->removeInCart($productVariationId, $userId);
    }

    public function addToWishlist($productVariationId, $userId)
    {
        return $this->product->addToWishlist($productVariationId, $userId);
    }

    public function removeInWishlist($productVariationId, $userId)
    {
        return $this->product->removeInWishlist($productVariationId, $userId);
    }

    public function getCartCountByUser($userId)
    {
        return $this->product->getCartCountByUser($userId);
    }

    public function getAllPaymentMethods()
    {
        return $this->product->getAllPaymentMethods();
    }

    public function getAllVouchers()
    {
        return $this->product->getAllVouchers();
    }

    public function createVoucher($data)
    {
        return $this->product->createVoucher($data);
    }

    public function updateVoucher($id, $data)
    {
        return $this->product->updateVoucher($id, $data);
    }

    public function deleteVoucher($id)
    {
        return $this->product->deleteVoucher($id);
    }

    public function removeInCartByProducts($buyerUserId, $products)
    {

        foreach($products as $product) {

            $productVariationId = $product['product_variation_id'];
            $this->removeInCart($productVariationId, $buyerUserId);

        }

    }

    public function subtractStoreStocksByProducts($products)
    {

        foreach($products as $product){

            $storeId = $product['store_id'];
            $productVariationId = $product['product_variation_id'];
            $quantity = $product['quantity'];

            $this->subtractStoreStock($storeId, $productVariationId, $quantity);

        }

    }

    public function calculateTotalByProducts($products)
    {
        $total = 0;

        foreach($products as $product){

            $sellingPrice = $product['selling_price'];
            $shippingPrice = $product['shipping_price'];
            $quantity = $product['quantity'];

            $total += ($sellingPrice*$quantity)+($shippingPrice*$quantity);

        }

        return $total;

    }

    public function findPaymentModeById($paymentModeId)
    {
        $paymentMode = $this->product->findPaymentModeById($paymentModeId);

        return $paymentMode;
    }

}