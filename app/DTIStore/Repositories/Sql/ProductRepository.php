<?php namespace App\DTIStore\Repositories;

use App\DTIStore\Helpers\SqlHelper;
use App\DTIStore\Helpers\StatusHelper;
use App\PaymentMode;
use App\Product;
use App\ProductVariation;
use App\UserCart;
use App\UserWishlist;
use App\Voucher;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ProductRepository implements ProductInterface
{

    public function create(array $data)
    {
        $product = Product::create($data);

        return $product;
    }

    public function find($id)
    {
        $product = Product::find($id);

        if (!$product) {
            return $product;
        }

        $product->variations = ProductVariation::where('product_id', $id)->get();

        return $product;
    }

    public function getAll()
    {

        $activeFlag = StatusHelper::ACTIVE;

        $sql = "SELECT 
                  products.id,
                  products.name,
                  product_categories.name AS category,
                  product_categories.code AS category_code,
                  product_categories.id AS product_category_id,
                  product_variations.id AS product_variation_id,
                  product_variations.size,
                  product_variations.metrics,
                  product_variations.cost_price,
                  product_variations.selling_price,
                  'null' as branch_quantity,
                  'null' as store_id,
                  (SELECT SUM(branch_stocks.`quantity`) FROM branch_stocks  WHERE branch_stocks.product_variation_id = product_variations.id) as total_branch_quantity,
                  (SELECT SUM(branch_stocks.`quantity`) FROM branch_stocks  WHERE branch_stocks.product_variation_id = product_variations.id) as total_quantity
                FROM
                  products 
                  INNER JOIN product_categories 
                    ON product_categories.id = products.product_category_id 
                  INNER JOIN product_variations 
                    ON product_variations.product_id = products.id 
                WHERE products.status = '{$activeFlag}' ";

        $products = DB::select($sql);

        return $products;
    }

    public function getCountAll()
    {
        $activeFlag = StatusHelper::ACTIVE;

        $sql = "SELECT 
                  count(*) as products_count
                FROM
                  products 
                  INNER JOIN product_categories 
                    ON product_categories.id = products.product_category_id 
                  INNER JOIN product_variations 
                    ON product_variations.product_id = products.id 
                WHERE products.status = '{$activeFlag}' ";

        $products = DB::select($sql);

        return $products[0]->products_count;
    }

    public function getCountByFilter(array $filter = [])
    {
        $activeFlag = StatusHelper::ACTIVE;
        $additionalSqlFilters = $this->getAdditionalSqlFilters($filter);

        $branchId = 'null';

        if (isset($filter['store_id'])) {
            $branchId = $filter['store_id'];
        }

        $sql = "SELECT 
                  count(*) as products_count
                FROM
                  products 
                  INNER JOIN product_categories 
                    ON product_categories.id = products.product_category_id 
                  INNER JOIN product_variations 
                    ON product_variations.product_id = products.id 
                  LEFT JOIN store_stocks
                    ON store_stocks.`product_variation_id` = product_variations.`id` AND store_stocks.store_id = {$branchId}
                WHERE products.status = '{$activeFlag}' AND product_variations.status = '{$activeFlag}'
                {$additionalSqlFilters} ";

        $products = DB::select($sql);

        return $products[0]->products_count;
    }

    public function getCountByTransactionId($transactionId, array $filter = [])
    {
        $activeFlag = StatusHelper::ACTIVE;
        $additionalSqlFilters = $this->getAdditionalSqlFilters($filter);

        $sql = "SELECT 
                  COUNT(*) AS products_count 
                FROM
                  products 
                  INNER JOIN product_categories 
                    ON product_categories.id = products.product_category_id 
                  INNER JOIN product_variations 
                    ON product_variations.product_id = products.id 
                  INNER JOIN transaction_items ON transaction_items.`product_variation_id` = product_variations.`id`
                  INNER JOIN transactions ON transactions.`id` = transaction_items.`transaction_id`
                  
                  LEFT JOIN store_stocks 
                    ON store_stocks.`product_variation_id` = product_variations.`id` 
                    AND store_stocks.store_id = transactions.`store_id`
                WHERE transaction_items.`transaction_id` = {$transactionId}
                {$additionalSqlFilters}";

        $products = DB::select($sql);

        return $products[0]->products_count;
    }

    public function filter(array $filter)
    {
        $additionalSqlFilters = $this->getAdditionalSqlFilters($filter);
        $paginationSql = SqlHelper::getPaginationByFilter($filter);

        $storeLeftJoinSql = "";
        $inWishListSelectSql = "0 as 'in_wishlist'";
        $inCartSelectSql = "0 as 'in_cart'";
        $cartQuantitySelectSql = "0 as 'cart_quantity'";
        $userId = null;

        if (isset($filter['store_id'])) {
            $storeId = $filter['store_id'];
            $storeLeftJoinSql = " AND store_stocks.store_id = {$storeId} ";
        }

        if (isset($filter['user_id'])) {
            $userId = $filter['user_id'];
            $inWishListSelectSql = "(SELECT CASE WHEN COUNT(user_wishlists.product_variation_id) > 0
                  THEN 1
                  ELSE 0
                  END FROM user_wishlists WHERE user_wishlists.user_id = {$userId} AND user_wishlists.product_variation_id = product_variations.id) as 'in_wishlist'";
            $inCartSelectSql = "(SELECT CASE WHEN SUM(user_carts.cart_quantity) > 0
                  THEN 1
                  ELSE 0
                  END FROM user_carts WHERE user_carts.user_id = {$userId} AND user_carts.product_variation_id = product_variations.id) as 'in_cart'";
            $cartQuantitySelectSql = "(SELECT SUM(cart_quantity) as cart_quantity FROM user_carts WHERE user_carts.user_id = {$userId} AND user_carts.product_variation_id = product_variations.id) as 'cart_quantity'";
        }

        $activeFlag = StatusHelper::ACTIVE;

        $sql = "SELECT 
                  products.name,
                  product_variations.size,
                  product_variations.metrics,
                  product_categories.name AS category,
                  product_conditions.name AS 'condition',
                  product_variations.cost_price,
                  product_variations.selling_price,
                  product_variations.shipping_price,
                  products.description,
                  (SELECT SUM(store_stocks.`quantity`) FROM store_stocks WHERE store_stocks.product_variation_id = product_variations.id) as total_quantity,
                  product_variations.id,
                  products.id as product_id,
                  products.code,
                  products.image_url,
                  product_categories.code AS category_code,
                  product_categories.id AS product_category_id,
                  product_conditions.id AS product_condition_id,
                  product_variations.id AS product_variation_id,
                  store_stocks.`quantity` as branch_quantity,
                  store_stocks.store_id,
                  users.firstname as 'seller_firstname',
                  users.middlename as 'seller_middlename',
                  users.lastname as 'seller_lastname',
                  {$inWishListSelectSql},
                  {$inCartSelectSql},
                  {$cartQuantitySelectSql},
                  (SELECT SUM(store_stocks.`quantity`) FROM store_stocks WHERE store_stocks.product_variation_id = product_variations.id) as total_branch_quantity
                FROM
                  products 
                  INNER JOIN product_categories 
                    ON product_categories.id = products.product_category_id 
                  INNER JOIN product_variations 
                    ON product_variations.product_id = products.id
                  LEFT JOIN product_conditions 
                    ON product_conditions.id = products.product_condition_id 
                  LEFT JOIN store_stocks
                    ON store_stocks.`product_variation_id` = product_variations.`id`
                    {$storeLeftJoinSql}
                  LEFT JOIN user_stores
                    ON user_stores.store_id = store_stocks.store_id
                  LEFT JOIN users
                    ON users.id = user_stores.user_id
                WHERE products.status = '{$activeFlag}' 
                AND product_variations.status = '{$activeFlag}'
                AND users.status != 'deleted'
                {$additionalSqlFilters} 
                {$paginationSql} ";

        $products = DB::select($sql);

        return $products;
    }

    public function getByTransactionId($transactionId, array $filter)
    {
        $additionalSqlFilters = $this->getAdditionalSqlFilters($filter);
        $paginationSql = SqlHelper::getPaginationByFilter($filter);

        $activeFlag = StatusHelper::ACTIVE;

        $sql = "SELECT 
                  product_variations.id,
                  products.id AS product_id,
                  products.name,
                  products.code,
                  products.image_url,
                  product_categories.name AS category,
                  product_categories.code AS category_code,
                  product_categories.id AS product_category_id,
                  product_variations.id AS product_variation_id,
                  product_variations.size,
                  product_variations.metrics,
                  product_variations.cost_price,
                  product_variations.selling_price,
                  store_stocks.`quantity` AS branch_quantity,
                  transaction_items.`quantity`,
                  store_stocks.store_id,
                  (SELECT 
                    SUM(store_stocks.`quantity`) 
                  FROM
                    store_stocks 
                  WHERE store_stocks.product_variation_id = product_variations.id) AS total_branch_quantity,
                  (SELECT 
                    SUM(store_stocks.`quantity`) 
                  FROM
                    store_stocks 
                  WHERE store_stocks.product_variation_id = product_variations.id) AS total_quantity 
                FROM
                  products 
                  INNER JOIN product_categories 
                    ON product_categories.id = products.product_category_id 
                  INNER JOIN product_variations 
                    ON product_variations.product_id = products.id 
                  INNER JOIN transaction_items 
                    ON transaction_items.`product_variation_id` = product_variations.`id`
                  INNER JOIN transactions
                    ON transactions.id = transaction_items.`transaction_id`
                  LEFT JOIN store_stocks 
                    ON store_stocks.`product_variation_id` = product_variations.`id` 
                    AND store_stocks.store_id = transactions.`store_id`
                WHERE transaction_items.`transaction_id` = {$transactionId} 
                {$additionalSqlFilters} 
                {$paginationSql} ";

        $products = DB::select($sql);

        return $products;
    }

    public function getFilterMeta($filter)
    {
        $limit = SqlHelper::getLimitByFilter($filter);
        $page = SqlHelper::getPageByFilter($filter);

        return [
            'length' => $this->getCountByFilter($filter),
            'limit' => $limit,
            'page' => $page
        ];
    }

    public function getByTransactionIdMeta($transactionId, $filter)
    {
        $limit = SqlHelper::getLimitByFilter($filter);
        $page = SqlHelper::getPageByFilter($filter);

        return [
            'length' => $this->getCountByTransactionId($transactionId, $filter),
            'limit' => $limit,
            'page' => $page
        ];
    }

    public function update($id, $data)
    {
        $product = Product::find($id);

        if (!$product) {
            return false;
        }

        $updated = $product->update($data);

        return $updated;
    }

    public function delete($id)
    {
        $product = Product::find($id);

        if (!$product) {
            return false;
        }

        if ($product->status == StatusHelper::DELETED) {
            return true;
        }

        $deleted = $product->update([
            'deleted_at' => Carbon::now()->toDateTimeString(),
            'status' => StatusHelper::DELETED,
            'name' => StatusHelper::flagDelete($product->name),
            'code' => StatusHelper::flagDelete($product->code)
        ]);

        return $deleted;
    }

    public function destroy($id)
    {
        $product = $this->find($id);

        if (!$product) {
            return false;
        }

        $destroyed = $product->delete();

        return $destroyed;
    }

    public function isDeleted($id)
    {
        $product = $this->find($id);

        if (!$product) {
            return true;
        }

        if ($product->status != StatusHelper::DELETED) {
            return false;
        }

        return true;
    }

    public function addToCart($productVariationId, $userId, $quantity)
    {
        $userCart = UserCart::firstOrCreate([
            'user_id' => $userId,
            'product_variation_id' => $productVariationId,
        ]);

        $userCart->update([
            'cart_quantity' => $quantity
        ]);

        return $userCart;
    }

    public function addToWishlist($productVariationId, $userId)
    {
        $userWishlist = UserWishlist::firstOrCreate([
            'user_id' => $userId,
            'product_variation_id' => $productVariationId
        ]);

        return $userWishlist;
    }

    public function removeInCart($productVariationId, $userId)
    {
        $userCart = UserCart::where('user_id',$userId)
            ->where('product_variation_id',$productVariationId)
            ->first();

        if(!$userCart){
            return true;
        }

        $deleted = $userCart->delete();

        return $deleted;
    }

    public function removeInWishlist($productVariationId, $userId)
    {
        $userWishlist = UserWishlist::where('user_id',$userId)
            ->where('product_variation_id',$productVariationId)
            ->first();

        if(!$userWishlist){
            return true;
        }

        $deleted = $userWishlist->delete();

        return $deleted;
    }

    public function getCartCountByUser($userId)
    {
        $count = UserCart::where('user_id', $userId)
            ->select(DB::raw('SUM(cart_quantity) as quantity'))
            ->first();

        if(!$count){
            return 0;
        }

        return $count->quantity;
    }

    public function getAllPaymentMethods()
    {
        return PaymentMode::all();
    }

    public function getAllVouchers()
    {
        return Voucher::all();
    }

    public function createVoucher($data)
    {
        return Voucher::create($data);
    }

    public function updateVoucher($id, $data)
    {
        $voucher = Voucher::find($id);

        if(!$voucher){
            return false;
        }

        $updated = $voucher->update($data);

        return $updated;
    }

    public function deleteVoucher($id)
    {
        $voucher = Voucher::find($id);

        if(!$voucher){
            return false;
        }

        $deleted = $voucher->delete();

        return $deleted;
    }

    public function findPaymentModeById($paymentModeId)
    {
        $paymentMode = PaymentMode::find($paymentModeId);

        return $paymentMode;
    }

    private function getAdditionalSqlFilters($filter)
    {
        $branchSql = "";
        $categorySql = "";
        $variationSql = "";
        $querySql = "";
        $orderSql = "";
        $order = "DESC";
        $orderBy = "";
        $fromSql = "";
        $toSql = "";

        if (isset($filter['store_id'])) {
            $branchId = $filter['store_id'];
            $branchSql = " AND store_stocks.store_id = {$branchId} ";
        }

        if (isset($filter['category_id'])) {
            $categoryId = $filter['category_id'];
            $categorySql = " AND product_categories.id = {$categoryId} ";
        }

        if (isset($filter['variation_id'])) {
            $variationId = $filter['variation_id'];
            $variationSql = " AND product_variations.id = {$variationId}";
        }

        if (isset($filter['q'])) {
            $categorySql = "";
            $query = $filter['q'];
            $querySql = " AND LOWER(
                CONCAT(
                    products.name,
                    ' ',
                    product_categories.name,
                    ' ',
                    product_variations.size,
                    ' ',
                    product_variations.metrics,
                    ' ',
                    products.name,
                    ' (',
                    product_variations.size,
                    ' ',
                    product_variations.metrics,
                    ') '
                )
            ) 
            LIKE LOWER('%{$query}%') ";
        }

        if (isset($filter['order'])) {
            $order = $filter['order'];
        }

        if (isset($filter['order_by']) || isset($filter['sort'])) {
            if (isset($filter['order_by'])) {
                $orderBy = $filter['order_by'];
            }
            if (isset($filter['sort'])) {
                $orderBy = $filter['sort'];
            }
        }

        switch ($orderBy) {
            case 'name':
                $orderSql = " ORDER BY products.{$orderBy} {$order}, product_variations.size ASC ";
                break;
            case 'date':
                $orderSql = " ORDER BY products.{$orderBy} {$order}";
                break;
            case 'selling_price':
                $orderSql = " ORDER BY product_variations.{$orderBy} {$order}";
                break;
            case 'cost_price':
                $orderSql = " ORDER BY product_variations.{$orderBy} {$order}";
                break;
            default:
                $orderSql = " ORDER BY products.name ASC";
                break;
        }

//        if(isset($filter['from'])) {
//            $from = $filter['from'];
//            $fromSql = " AND products.created_at >= DATE('{$from}')";
//        }
//
//        if(isset($filter['to'])) {
//            $to = $filter['to'];
//            $toSql = " AND products.created_at <= DATE('{$to}')";
//        }

        $additionalSql = $branchSql . $categorySql . $variationSql . $querySql . $orderSql . $fromSql . $toSql;

        return $additionalSql;
    }

    public function findVoucherByCode($voucherCode)
    {
        $voucher = Voucher::where('code',$voucherCode)->first();

        return $voucher;
    }

}