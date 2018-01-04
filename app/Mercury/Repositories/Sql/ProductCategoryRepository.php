<?php namespace App\Mercury\Repositories;

use App\Mercury\Helpers\StatusHelper;
use App\ProductCategory as Category;
use App\Mercury\Helpers\SqlHelper;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ProductCategoryRepository implements ProductCategoryInterface {

    private $activeFlag;

    public function __construct()
    {
        $this->activeFlag = StatusHelper::ACTIVE;
    }

    public function find($id)
    {
        $category = Category::find($id);

        return $category;
    }

    public function getAll()
    {
        $categories = Category::whereIn('status', [StatusHelper::ACTIVE])->get();

        return $categories;
    }

    public function filter(array $filter)
    {
        $additionalSqlFilter = $this->getAdditionalSqlFilters($filter);

        $paginationSql = SqlHelper::getPaginationByFilter($filter);

        $query = "SELECT
                    product_categories.id,
                    product_categories.name,
                    product_categories.code,
                    product_categories.color
                  FROM
                    product_categories
                  WHERE
                    product_categories.status = '{$this->activeFlag}'
                  {$additionalSqlFilter}
                  {$paginationSql}";

        $categories = DB::select($query);

        return $categories;
    }

    private function getAdditionalSqlFilters($filter)
    {
        $searchQuery = "";

        $order = 'DESC';

        if(isset($filter['order'])) {
            $order = $filter['order'];
        }

        $orderSql = "ORDER BY product_categories.name {$order}";

        if(isset($filter['q'])) {
            $query = $filter['q'];
            $searchQuery = " AND LOWER(
                CONCAT(
                    product_categories.name,' '
                )
            ) LIKE LOWER('%{$query}%') ";
        }

        $additionalQueries = $searchQuery . $orderSql;

        return $additionalQueries;
    }

    public function getFilterMeta(array $filter)
    {
        $limit = SqlHelper::getLimitByFilter($filter);
        $page = SqlHelper::getPageByFilter($filter);

        return [
            'length' => $this->getCountByFilter($filter),
            'limit' => $limit,
            'page' => $page
        ];
    }

    public function getCountByFilter(array $filter)
    {
        $additionalFilters = $this->getAdditionalSqlFilters($filter);

        $query = "SELECT
                    count(*) as product_categories_count
                  FROM
                    product_categories
                  WHERE
                    product_categories.status = '{$this->activeFlag}'
                  {$additionalFilters}";

        $count = DB::select($query);

        return $count[0]->product_categories_count;
    }

    public function create(array $data)
    {
        $category = Category::create($data);

        return $category;
    }

    public function update($id, $data)
    {
        $category = $this->find($id);

        if(!$category){
            return false;
        }

        $updated = $category->update($data);

        return $updated;
    }

    public function delete($id)
    {
        $category = $this->find($id);
        
        if(!$category){
            return false;
        }

        if($category->status == StatusHelper::DELETED){
            return true;
        }

        $deleted = $category->update([
            'deleted_at' => Carbon::now()->toDateTimeString(),
            'status' => StatusHelper::DELETED,
            'name' => StatusHelper::flagDelete($category->name),
            'code' => StatusHelper::flagDelete($category->code)
        ]);

        return $deleted;
    }

    public function isDeleted($id)
    {
        $category = $this->find($id);

        if(!$category){
            return true;
        }

        if($category->status != StatusHelper::DELETED){
            return false;
        }

        return true;
    }

    public function destroy($id)
    {
        $category = $this->find($id);

        if(!$category){
            return false;
        }

        $destroyed = $category->delete();

        return $destroyed;
    }

    public function getByTransactionId($transactionId)
    {
        $sql = "SELECT DISTINCT 
                  (product_categories.id) AS product_category_id,
                  product_categories.* 
                FROM
                  product_categories 
                  INNER JOIN products 
                    ON products.`product_category_id` = product_categories.`id` 
                  INNER JOIN product_variations 
                    ON product_variations.`product_id` = products.`id` 
                  INNER JOIN transaction_items 
                    ON transaction_items.`product_variation_id` = product_variations.`id` 
                WHERE transaction_items.`transaction_id` = {$transactionId}";

        $categories = DB::select($sql);

        return $categories;
    }

}