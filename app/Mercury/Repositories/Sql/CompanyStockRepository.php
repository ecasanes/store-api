<?php namespace App\Mercury\Repositories;

use App\Mercury\Helpers\StatusHelper;
use App\CompanyStock;
use App\ProductVariation;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CompanyStockRepository implements CompanyStockInterface {

    public function create(array $data)
    {

        if(!isset($data['product_variation_id'])){
            return null;
        }

        $variationId = $data['product_variation_id'];

        $companyStock = $this->findByVariationId($variationId);

        if(!$companyStock){
            $companyStock = CompanyStock::create($data);
        }

        return $companyStock;
    }

    public function find($id)
    {
        $companyStock = CompanyStock::find($id);

        return $companyStock;
    }

    public function getAll()
    {
        $companyStocks = CompanyStock::all();

        return $companyStocks;
    }

    public function filter(array $filter = [])
    {

        $querySql = "";

        if(isset($filter['q'])){
            $queryString = $filter['q'];
            $querySql = " AND LOWER(company_stocks_query.product_name) LIKE LOWER('%{$queryString}%') ";
        }
        $additionalFilters = $this->getAdditionalFilters($filter);
        $transactionDateFilter = $this->getTransactionDateFilter($filter);
        $sql = "SELECT 
                company_stocks_query.*, 
                (company_stocks_query.sale_item_count - company_stocks_query.return_sale_item_count) AS sold_items
                FROM 
                (SELECT
                CONCAT(products.name,' (',ROUND(product_variations.size),' ',product_variations.metrics,') ', products.code) as product_name,
                products.`code`,
                company_stocks.`quantity`,
                company_stocks.`product_variation_id`,
                company_stocks.`product_variation_id` as id,
                company_stocks.`quantity` as branch_quantity,
                company_stocks.`quantity` as company_quantity,
                product_variations.size,
                product_variations.`metrics`,
                product_variations.`selling_price`,
                product_variations.`cost_price`,
                products.`name`,
                products.`image_url`,
                
                (SELECT 
                    CASE WHEN SUM(transaction_items.`quantity`) IS NULL THEN 0 ELSE SUM(transaction_items.`quantity`) END
                  FROM
                    transaction_items 
                    INNER JOIN transactions 
                      ON transactions.id = transaction_items.`transaction_id` 
                    INNER JOIN transaction_types 
                      ON transaction_types.id = transactions.`transaction_type_id` 
                  WHERE transactions.status = 'active' 
                    AND transaction_types.`code` = 'sale' 
                    AND transaction_items.`product_variation_id` = company_stocks.`product_variation_id`
                    {$transactionDateFilter}) AS sale_item_count,
                    
                    (SELECT 
                    CASE WHEN SUM(transaction_items.`quantity`) IS NULL THEN 0 ELSE SUM(transaction_items.`quantity`) END 
                  FROM
                    transaction_items 
                    INNER JOIN transactions 
                      ON transactions.id = transaction_items.`transaction_id` 
                    INNER JOIN transaction_types 
                      ON transaction_types.id = transactions.`transaction_type_id` 
                  WHERE transactions.status = 'active' 
                    AND transaction_types.`code` = 'return_sale' 
                    AND transaction_items.`product_variation_id` = company_stocks.`product_variation_id`) AS return_sale_item_count
                
                FROM 
                company_stocks
                INNER JOIN product_variations ON product_variations.id = company_stocks.`product_variation_id`
                INNER JOIN products ON products.id = product_variations.`product_id`
                ) AS company_stocks_query
                WHERE company_stocks_query.id IS NOT NULL 
                 {$additionalFilters}
                 {$querySql}";

//        dd($sql);
        $companyStocks = DB::select($sql);

        return $companyStocks;
    }

    public function update($id, $data)
    {
        $companyStock = $this->find($id);

        if(!$companyStock){
            return false;
        }

        $updated = $companyStock->update($data);

        return $updated;
    }

    public function delete($id)
    {
        $companyStock = $this->find($id);

        if(!$companyStock){
            return false;
        }

        if($companyStock->status == StatusHelper::DELETED){
            return true;
        }

        $deleted = $companyStock->update([
            'deleted_at' => Carbon::now()->toDateTimeString(),
            'status' => StatusHelper::DELETED,
        ]);

        return $deleted;
    }

    public function destroy($id)
    {
        $companyStock = $this->find($id);

        if(!$companyStock){
            return false;
        }

        $destroyed = $companyStock->delete();

        return $destroyed;
    }

    public function isDeleted($id)
    {
        $companyStock = $this->find($id);

        if(!$companyStock){
            return true;
        }

        if($companyStock->status != StatusHelper::DELETED){
            return false;
        }

        return true;
    }

    public function addStocksByProductId($productId, $additionalStocks)
    {
        $sql = "UPDATE 
                  company_stocks 
                SET
                  company_stocks.quantity = company_stocks.`quantity` + {$additionalStocks}
                WHERE company_stocks.`id` IN 
                  (SELECT 
                    cs.id 
                  FROM
                    (SELECT 
                      company_stocks.* 
                    FROM
                      company_stocks 
                      INNER JOIN product_variations 
                        ON product_variations.`id` = company_stocks.`product_variation_id` 
                    WHERE product_variations.`product_id` = {$productId}) cs)";

        $updated = DB::update($sql);

        return $updated;
    }

    public function subtractStocksByProductId($productId, $additionalStocks)
    {
        $sql = "UPDATE 
                  company_stocks 
                SET
                  company_stocks.quantity = company_stocks.`quantity` - {$additionalStocks}
                WHERE company_stocks.`id` IN 
                  (SELECT 
                    cs.id 
                  FROM
                    (SELECT 
                      company_stocks.* 
                    FROM
                      company_stocks 
                      INNER JOIN product_variations 
                        ON product_variations.`id` = company_stocks.`product_variation_id` 
                    WHERE product_variations.`product_id` = {$productId}) cs)";

        $updated = DB::update($sql);

        return $updated;
    }

    public function addStocksByVariationId($variationId, $quantity)
    {
        $companyStock = $this->findByVariationId($variationId);

        if(!$companyStock){
            $companyStock = $this->create([
                'product_variation_id' => $variationId,
                'quantity' => $quantity
            ]);

            return $companyStock;
        }

        $updated = $companyStock->update([
            'quantity' => $companyStock->quantity + $quantity
        ]);

        return $updated;
    }

    public function subtractStocksByVariationId($variationId, $quantity)
    {

        return DB::transaction(function() use ($quantity, $variationId) {
            $companyStock = $this->findByVariationId($variationId);

            if (!$companyStock) {
                $companyStock = $this->create([
                    'product_variation_id' => $variationId,
                    'quantity' => $quantity
                ]);

                return true;
            }

            $updated = $companyStock->update([
                'quantity' => $companyStock->quantity - $quantity
            ]);

            return $updated;


        });
    }

    public function findByVariationId($variationId)
    {
        $companyStocks = CompanyStock::where('product_variation_id', $variationId)->first();

        return $companyStocks;
    }

    public function getAdditionalFilters($filters)
    {
        $columnPrefix = 'company_stocks_query';

        $sortFilter = "";
        $orderFilter = "DESC";
        $fromSql = "";
        $toSql = "";

        if(isset($filters['order'])) {
            $orderFilter = $filters['order'];
        }

        if(isset($filters['sort'])) {
            $sort = $filters['sort'];
            switch ($sort) {
                case 'total_sold_items':
                    $sortFilter = "ORDER BY sold_items {$orderFilter}";
                    break;

                case 'product_name':
                    $sortFilter = "ORDER BY product_name {$orderFilter}";
                    break;

                case 'current_inventory':
                    $sortFilter = "ORDER BY company_quantity {$orderFilter}";
                    break;
            }

        }

        $queryFilters = $sortFilter;

        return $queryFilters;

    }

    public function getCompanyStocksByItemIds(array $itemIds, $companyId = null)
    {
        $companyStocks = CompanyStock::whereIn('product_variation_id', $itemIds);

        if($companyId){
            $companyStocks->where('company_id', $companyId);
        }

        $companyStocks = $companyStocks->get();

        return $companyStocks;

    }

    private function getTransactionDateFilter($filters) {

        $columnPrefix = 'transactions';
        $fromSql = '';
        $toSql = '';

        if(isset($filters['from'])) {
            $from = $filters['from'];
            $fromSql = " AND DATE({$columnPrefix}.created_at) >= DATE('{$from}')";
        }

        if(isset($filters['to'])) {
            $to = $filters['to'];
            $toSql = " AND DATE({$columnPrefix}.created_at) <= DATE('{$to}') ";
        }

        $filters = $fromSql . $toSql;

        return $filters;
    }
}