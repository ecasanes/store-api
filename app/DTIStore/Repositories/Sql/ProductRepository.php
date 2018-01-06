<?php namespace App\DTIStore\Repositories;

use App\DTIStore\Helpers\SqlHelper;
use App\DTIStore\Helpers\StatusHelper;
use App\Product;
use App\ProductVariation;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ProductRepository implements ProductInterface {

    public function create(array $data)
    {
        $product = Product::create($data);

        return $product;
    }

    public function find($id)
    {
        $product = Product::find($id);

        if(!$product){
            return $product;
        }

        $product->variations = ProductVariation::where('product_id',$id)->get();

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
                  product_variations.franchisee_price,
                  company_stocks.`quantity` as company_quantity,
                  'null' as branch_quantity,
                  'null' as branch_id,
                  (SELECT SUM(branch_stocks.`quantity`) FROM branch_stocks  WHERE branch_stocks.product_variation_id = product_variations.id) as total_branch_quantity,
                  (SELECT SUM(branch_stocks.`quantity`) FROM branch_stocks  WHERE branch_stocks.product_variation_id = product_variations.id) + company_stocks.quantity as total_quantity
                FROM
                  products 
                  INNER JOIN product_categories 
                    ON product_categories.id = products.product_category_id 
                  INNER JOIN product_variations 
                    ON product_variations.product_id = products.id 
                  LEFT JOIN company_stocks 
                    ON company_stocks.`product_variation_id` = product_variations.`id`
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
                  LEFT JOIN company_stocks 
                    ON company_stocks.`product_variation_id` = product_variations.`id`
                WHERE products.status = '{$activeFlag}' ";

        $products = DB::select($sql);

        return $products[0]->products_count;
    }

    public function getCountByFilter(array $filter = [])
    {
        $activeFlag = StatusHelper::ACTIVE;
        $additionalSqlFilters = $this->getAdditionalSqlFilters($filter);

        $branchId = 'null';

        if(isset($filter['branch_id'])){
            $branchId = $filter['branch_id'];
        }

        $sql = "SELECT 
                  count(*) as products_count
                FROM
                  products 
                  INNER JOIN product_categories 
                    ON product_categories.id = products.product_category_id 
                  INNER JOIN product_variations 
                    ON product_variations.product_id = products.id 
                  LEFT JOIN company_stocks 
                    ON company_stocks.`product_variation_id` = product_variations.`id`
                  LEFT JOIN branch_stocks
                    ON branch_stocks.`product_variation_id` = product_variations.`id` AND branch_stocks.branch_id = {$branchId}
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
                  LEFT JOIN company_stocks 
                    ON company_stocks.`product_variation_id` = product_variations.`id` 
                  LEFT JOIN branch_stocks 
                    ON branch_stocks.`product_variation_id` = product_variations.`id` 
                    AND branch_stocks.branch_id = transactions.`branch_id`
                WHERE transaction_items.`transaction_id` = {$transactionId}
                {$additionalSqlFilters}";

        $products = DB::select($sql);

        return $products[0]->products_count;
    }

    public function filter(array $filter)
    {
        $additionalSqlFilters = $this->getAdditionalSqlFilters($filter);
        $paginationSql = SqlHelper::getPaginationByFilter($filter);

        $branchId = 'null';

        if(isset($filter['branch_id'])){
            $branchId = $filter['branch_id'];
        }

        $activeFlag = StatusHelper::ACTIVE;

        $sql = "SELECT 
                  products.name,
                  product_variations.size,
                  product_variations.metrics,
                  product_categories.name AS category,
                  product_variations.cost_price,
                  product_variations.selling_price,
                  product_variations.franchisee_price,
                  company_stocks.`quantity` as company_quantity,
                  (SELECT SUM(branch_stocks.`quantity`) FROM branch_stocks WHERE branch_stocks.product_variation_id = product_variations.id) + company_stocks.quantity as total_quantity,
                  product_variations.id,
                  products.id as product_id,
                  products.code,
                  products.image_url,
                  product_categories.code AS category_code,
                  product_categories.id AS product_category_id,
                  product_variations.id AS product_variation_id,
                  branch_stocks.`quantity` as branch_quantity,
                  branch_stocks.branch_id,
                  (SELECT SUM(branch_stocks.`quantity`) FROM branch_stocks WHERE branch_stocks.product_variation_id = product_variations.id) as total_branch_quantity
                FROM
                  products 
                  INNER JOIN product_categories 
                    ON product_categories.id = products.product_category_id 
                  INNER JOIN product_variations 
                    ON product_variations.product_id = products.id 
                  LEFT JOIN company_stocks 
                    ON company_stocks.`product_variation_id` = product_variations.`id`
                  LEFT JOIN branch_stocks
                    ON branch_stocks.`product_variation_id` = product_variations.`id` AND branch_stocks.branch_id = {$branchId}
                WHERE products.status = '{$activeFlag}' AND product_variations.status = '{$activeFlag}'
                {$additionalSqlFilters} 
                {$paginationSql} ";
//        dd($sql);
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
                  product_variations.franchisee_price,
                  company_stocks.`quantity` AS company_quantity,
                  branch_stocks.`quantity` AS branch_quantity,
                  transaction_items.`quantity`,
                  branch_stocks.branch_id,
                  (SELECT 
                    SUM(branch_stocks.`quantity`) 
                  FROM
                    branch_stocks 
                  WHERE branch_stocks.product_variation_id = product_variations.id) AS total_branch_quantity,
                  (SELECT 
                    SUM(branch_stocks.`quantity`) 
                  FROM
                    branch_stocks 
                  WHERE branch_stocks.product_variation_id = product_variations.id) + company_stocks.quantity AS total_quantity 
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
                  LEFT JOIN company_stocks 
                    ON company_stocks.`product_variation_id` = product_variations.`id` 
                  LEFT JOIN branch_stocks 
                    ON branch_stocks.`product_variation_id` = product_variations.`id` 
                    AND branch_stocks.branch_id = transactions.`branch_id`
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

        if(!$product){
            return false;
        }

        $updated = $product->update($data);

        return $updated;
    }

    public function delete($id)
    {
        $product = $this->find($id);

        if(!$product){
            return false;
        }

        if($product->status == StatusHelper::DELETED){
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

        if(!$product){
            return false;
        }

        $destroyed = $product->delete();

        return $destroyed;
    }

    public function isDeleted($id)
    {
        $product = $this->find($id);

        if(!$product){
            return true;
        }

        if($product->status != StatusHelper::DELETED){
            return false;
        }

        return true;
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

        if(isset($filter['branch_id'])) {
            $branchId = $filter['branch_id'];
            $branchSql = " AND branch_stocks.branch_id = {$branchId} ";
        }
        
        if(isset($filter['category_id'])){
            $categoryId = $filter['category_id'];
            $categorySql = " AND product_categories.id = {$categoryId} ";
        }

        if(isset($filter['variation_id'])) {
            $variationId = $filter['variation_id'];
            $variationSql = " AND product_variations.id = {$variationId}";
        }

        if(isset($filter['q'])){
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

        if(isset($filter['order'])){
            $order = $filter['order'];
        }

        if(isset($filter['order_by']) || isset($filter['sort'])){
            if(isset($filter['order_by'])) {
                $orderBy = $filter['order_by'];
            }
            if(isset($filter['sort'])) {
                $orderBy = $filter['sort'];
            }
        }

        switch($orderBy){
            case 'name':
                $orderSql = " ORDER BY products.{$orderBy} {$order}, product_variations.size ASC ";
                break;
            case 'date':
                $orderSql = " ORDER BY products.{$orderBy} {$order}";
                break;
            case 'selling_price':
                $orderSql = " ORDER BY product_variations.{$orderBy} {$order}";
                break;
            case 'franchisee_price':
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
}