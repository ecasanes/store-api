<?php namespace App\Mercury\Repositories;

use App\Mercury\Helpers\SqlHelper;
use App\Mercury\Helpers\StatusHelper;
use App\Transaction;
use App\TransactionItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TransactionRepository implements TransactionInterface
{

    public function create(array $data)
    {
        $transaction = Transaction::create($data);

        return $transaction;
    }

    public function find($id)
    {
        $activeFlag = StatusHelper::ACTIVE;
        $returnSaleFlag = StatusHelper::RETURN_SALE;

        $transaction = Transaction::where('transactions.id', $id)
            ->join('transaction_types', 'transaction_types.id', '=', 'transactions.transaction_type_id')
            ->select(
                'transactions.*',
                'transaction_types.name as transaction_type_name',
                'transaction_types.code as transaction_type',
                DB::raw('(SELECT COUNT(*) FROM transactions as transact WHERE transact.referenced_transaction_id = transactions.id) as reference_count'),
                DB::raw("(SELECT ROUND(SUM(transaction_items.product_selling_price*transaction_items.quantity), 2) as total FROM transaction_items 
                WHERE transaction_items.transaction_id = transactions.id) as total"),
                DB::raw("(SELECT COUNT(*) FROM transactions as transact INNER JOIN transaction_types ON transaction_types.id = transact.transaction_type_id WHERE transaction_types.code = '{$returnSaleFlag}' AND transact.status = '{$activeFlag}' AND transact.or_no = transactions.or_no) as has_returns")
            )
            ->first();

        if (!$transaction) {
            return null;
        }

        $branchId = $transaction->branch_id;

        $transaction->items = $this->getItemsByTransactionId($transaction->id);
        $transaction->returns = $this->getReturnItemsByOrNumber($transaction->or_no, $branchId);

        return $transaction;
    }

    public function findByOr($orNo, $transactionTypeCode = StatusHelper::SALE, $includeVoid = false, $branchId = null)
    {

        $activeFlag = StatusHelper::ACTIVE;

        $transaction = Transaction::where('transactions.or_no', $orNo)
            ->join('transaction_types', 'transaction_types.id', '=', 'transactions.transaction_type_id')
            ->where('transaction_types.code', $transactionTypeCode)
            ->select(
                'transactions.*',
                DB::raw("(SELECT branches.name from branches where branches.id = transactions.branch_id LIMIT 1) as branch_name"),
                'transaction_types.name as transaction_type_name',
                'transaction_types.code as transaction_type',
                DB::raw('(SELECT COUNT(*) FROM transactions as transact WHERE transact.referenced_transaction_id = transactions.id) as reference_count')
            );

        if ($branchId) {
            $transaction = $transaction->where('transactions.branch_id','=',$branchId);
        }

        if ($includeVoid) {
            $transaction = $transaction->first();
        }

        if (!$includeVoid) {
            $transaction = $transaction->where('transactions.status', '=', $activeFlag)->first();
        }

        if (!$transaction) {
            return null;
        }

        $branchId = $transaction->branch_id;

        $transaction->items = $this->getItemsByTransactionId($transaction->id);
        $transaction->returns = $this->getReturnItemsByOrNumber($transaction->or_no, $branchId);

        return $transaction;
    }

    public function findReturnSaleByOr($orNo, $branchId = null)
    {
        $transactionTypeCode = StatusHelper::RETURN_SALE;
        $activeFlag = StatusHelper::ACTIVE;

        $transaction = Transaction::where('transactions.or_no', $orNo)
            ->join('transaction_types', 'transaction_types.id', '=', 'transactions.transaction_type_id')
            ->where('transaction_types.code', $transactionTypeCode)
            ->where('transactions.status', '=', $activeFlag)// means that it is not voided yet
            ->select(
                'transactions.*',
                'transaction_types.name as transaction_type_name',
                'transaction_types.code as transaction_type',
                DB::raw('(SELECT COUNT(*) FROM transactions as transact WHERE transact.referenced_transaction_id = transactions.id) as reference_count')
            );

        if($branchId) {
            $transaction = $transaction->where('transactions.branch_id','=',$branchId);
        }

        $transaction = $transaction->first();

        if (!$transaction) {
            return null;
        }

        $branchId = $transaction->branch_id;

        $transaction->items = $this->getItemsByTransactionId($transaction->id);
        $transaction->returns = $this->getReturnItemsByOrNumber($transaction->or_no, $branchId);

        return $transaction;
    }

    public function getAll()
    {
        $transactions = Transaction::whereIn('status', [StatusHelper::ACTIVE])->get();

        return $transactions;
    }

    public function getCountByFilter(array $filter = [])
    {
        $deletedFlag = StatusHelper::DELETED;
        $activeFlag = StatusHelper::ACTIVE;

        $additionalSqlFilters = $this->getAdditionalSqlFilters($filter);

        $sql = "SELECT count(*) as transaction_count FROM (SELECT 
                  transactions.*,
                  branches.name as branch_name,
                  transaction_types.`code` AS transaction_type,
                  transaction_types.`name` AS transaction_type_name,
                  transaction_types.`description` AS transaction_type_description,
                  transaction_types.group,
                  (SELECT 
                    GROUP_CONCAT(
                      products.`name`,
                      ' ',
                      product_variations.`size`,
                      ' ',
                      product_variations.`metrics`,
                      ' ',
                      products.name,
                      ' (',
                      product_variations.`size`,
                      ' ',
                      product_variations.`metrics`,
                      ')'
                    ) 
                  FROM
                    transaction_items 
                    INNER JOIN product_variations 
                      ON product_variations.`id` = transaction_items.`product_variation_id` 
                    INNER JOIN products 
                      ON products.`id` = product_variations.`product_id` 
                  WHERE transaction_items.`transaction_id` = transactions.`id`) AS query_string 
                FROM
                  transactions 
                  INNER JOIN transaction_types 
                    ON transaction_types.`id` = transactions.`transaction_type_id` 
                  LEFT JOIN branches
                    ON branches.id = transactions.branch_id
                WHERE transactions.id IS NOT NULL ) AS transaction_query 
                WHERE transaction_query.id IS NOT NULL
                {$additionalSqlFilters}  ";

        $products = DB::select($sql);

        return $products[0]->transaction_count;
    }

    public function filter(array $filter)
    {
        $additionalSqlFilters = $this->getAdditionalSqlFilters($filter);
        $paginationSql = SqlHelper::getPaginationByFilter($filter);

        $deletedFlag = StatusHelper::DELETED;
        $activeFlag = StatusHelper::ACTIVE;

        /*
         * (SELECT
                    CONCAT('<ul><li>',
                    TRIM(GROUP_CONCAT(
                      products.`name`,
                      ' - ',
                      ROUND(product_variations.`size`),
                      ' ',
                      product_variations.`metrics`,
                      ' (',
                      ROUND(transaction_items.quantity),
                      ')' separator '</li><li>'
                    )),'</ul>')
         */

        $sql = "SELECT * FROM (SELECT
                  transactions.or_no,
                  transaction_types.`name` AS transaction_type_name,
                  transactions.customer_firstname,
                  transactions.customer_lastname,
                  (SELECT 
                    TRIM(GROUP_CONCAT(
                      transaction_items.`product_name`,
                      ' - ',
                      ROUND(transaction_items.`product_size`),
                      ' ',
                      transaction_items.`product_metrics`,
                      ' (',
                      ROUND(transaction_items.quantity),
                      ')' separator ', '
                    )
                  )
                  FROM
                    transaction_items 
                  WHERE transaction_items.`transaction_id` = transactions.`id`) AS items_string,
                  (SELECT ROUND(SUM(transaction_items.product_selling_price*transaction_items.quantity),2) 
                  FROM transaction_items WHERE transaction_items.transaction_id = transactions.id) as total,
                  (SELECT ROUND(SUM(transaction_items.product_franchisee_price*transaction_items.quantity),2) 
                  FROM transaction_items WHERE transaction_items.transaction_id = transactions.id) as franchisee_total,
                  branches.name as branch_name,
                  transactions.staff_firstname,
                  transactions.staff_lastname,
                  transactions.created_at,
                  transactions.transaction_type_id,
                  transactions.staff_id,
                  transactions.customer_user_id,
                  transactions.customer_phone,
                  transactions.staff_phone,
                  transactions.price_rule_id,
                  transactions.id,
                  transactions.deleted_at,
                  transactions.updated_at,
                  transactions.user_id,
                  transactions.branch_id,
                  transactions.price_rule_code,
                  transactions.discount,
                  transactions.customer_id,
                  transactions.referenced_transaction_id,
                  transactions.remarks,
                  transactions.discount_type,
                  transactions.grand_total,
                  transactions.status,
                  transactions.invoice_no,
                  transactions.sub_type,
                  transactions.branch_type,
                  transaction_types.code AS transaction_type,
                  transaction_types.`description` AS transaction_type_description,
                  transaction_types.group,
                  (SELECT 
                    TRIM(GROUP_CONCAT(
                      transaction_items.`product_name`,
                      ' - ',
                      ROUND(transaction_items.`product_size`),
                      ' ',
                      transaction_items.`product_metrics`,
                      ' (',
                      ROUND(transaction_items.quantity),
                      '), ',
                      
                      transaction_items.`shortover_product_name`,
                      ' - ',
                      ROUND(transaction_items.`shortover_product_size`),
                      ' ',
                      transaction_items.`shortover_product_metrics`,
                      ' (',
                      ROUND(transaction_items.quantity),
                      ')' separator ', '
                    ))
                  FROM
                    transaction_items
                  WHERE transaction_items.`transaction_id` = transactions.`id`) AS shortover_string,
                  (SELECT 
                    GROUP_CONCAT(
                      products.`name`,
                      ' ',
                      product_variations.`size`,
                      ' ',
                      product_variations.`metrics`,
                      ' ',
                      products.`name`,
                      ' (',
                      product_variations.`size`,
                      ' ',
                      product_variations.`metrics`,
                      ')'
                    ) 
                  FROM
                    transaction_items 
                    INNER JOIN product_variations 
                      ON product_variations.`id` = transaction_items.`product_variation_id` 
                    INNER JOIN products 
                      ON products.`id` = product_variations.`product_id` 
                  WHERE transaction_items.`transaction_id` = transactions.`id`) AS query_string 
                FROM
                  transactions
                  INNER JOIN transaction_types 
                    ON transaction_types.`id` = transactions.`transaction_type_id` 
                  LEFT JOIN branches
                    ON branches.id = transactions.branch_id
                WHERE transactions.id IS NOT NULL ) AS transaction_query 
                WHERE transaction_query.id IS NOT NULL
                {$additionalSqlFilters} 
                {$paginationSql} ";
//        dd($filter);
        #removed "ORDER BY transaction_query.id DESC"
        $products = DB::select($sql);

//        dump($sql);

        return $products;
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

    public function update($id, $data)
    {
        $transaction = Transaction::find($id);

        if (!$transaction) {
            return false;
        }

        $updated = $transaction->update($data);

        return $updated;
    }

    public function delete($id)
    {
        $transaction = Transaction::find($id);

        if (!$transaction) {
            return false;
        }

        if ($transaction->status == StatusHelper::DELETED) {
            return true;
        }

        $deleted = $transaction->update([
            'deleted_at' => Carbon::now()->toDateTimeString(),
            'status' => StatusHelper::DELETED,
        ]);

        return $deleted;
    }

    public function destroy($id)
    {
        $transaction = Transaction::find($id);

        if (!$transaction) {
            return false;
        }

        $destroyed = $transaction->delete();

        return $destroyed;
    }

    public function isDeleted($id)
    {
        $transaction = Transaction::find($id);

        if (!$transaction) {
            return true;
        }

        if ($transaction->status != StatusHelper::DELETED) {
            return false;
        }

        return true;
    }

    public function getByOr($orNumber, $transactionTypeCode = StatusHelper::SALE, $branchId = null)
    {
        $activeFlag = StatusHelper::ACTIVE;

        $transactions = Transaction::where('transactions.or_no', $orNumber)
            ->join('transaction_types', 'transaction_types.id', '=', 'transactions.transaction_type_id')
            ->where('transaction_types.code', $transactionTypeCode)
            ->where('transactions.status', '=', $activeFlag)
            ->select(
                'transactions.*',
                'transaction_types.name as transaction_type_name',
                'transaction_types.code as transaction_type',
                DB::raw('(SELECT COUNT(*) FROM transactions as transact WHERE transact.referenced_transaction_id = transactions.id) as reference_count')
            );

        if($branchId){
            $transactions = $transactions->where('transactions.branch_id','=',$branchId);
        }

        $transactions = $transactions->get();

        return $transactions;
    }

    public function getByInvoiceNo($invoiceNo, $transactionTypeCode = StatusHelper::SALE)
    {
        $activeFlag = StatusHelper::ACTIVE;

        $transactions = Transaction::where('transactions.invoice_no', $invoiceNo)
            ->join('transaction_types', 'transaction_types.id', '=', 'transactions.transaction_type_id')
            ->where('transaction_types.code', $transactionTypeCode)
            ->where('transactions.status', '=', $activeFlag)
            ->select(
                'transactions.*',
                'transaction_types.name as transaction_type_name',
                'transaction_types.code as transaction_type',
                DB::raw('(SELECT COUNT(*) FROM transactions as transact WHERE transact.referenced_transaction_id = transactions.id) as reference_count')
            )
            ->get();

        return $transactions;
    }

    public function getTotalSales($filter)
    {
        $saleFlag = StatusHelper::SALE;
        $activeFlag = StatusHelper::ACTIVE;

        $sql = "SELECT 
                  CASE
                    WHEN SUM(transaction_items.`quantity` * transaction_items.`product_franchisee_price`) IS NULL AND transactions.sub_type = 'delivery'
                    THEN 0 
                    WHEN SUM(transaction_items.`quantity` * transaction_items.`product_selling_price`) IS NULL
                    THEN 0 
                    WHEN transactions.sub_type = 'delivery'
                    THEN SUM(transaction_items.`quantity` * transaction_items.`product_franchisee_price`)
                    ELSE SUM(transaction_items.`quantity` * transaction_items.`product_selling_price`)
                  END AS total_sales 
                FROM
                  transaction_items 
                  INNER JOIN transactions 
                    ON transactions.`id` = transaction_items.`transaction_id` 
                  INNER JOIN transaction_types 
                    ON transaction_types.`id` = transactions.`transaction_type_id` 
                WHERE transaction_types.`code` = '{$saleFlag}' 
                  AND transactions.status = '{$activeFlag}' ";

        $sql .= $this->getAdditionalTransactionSpecificFilters($filter);

        $totalSales = $totalSales = DB::select($sql)[0]->total_sales;

        return $totalSales;

    }

    public function getTopItems($filter)
    {
        $activeFlag = StatusHelper::ACTIVE;
        $saleFlag = StatusHelper::SALE;

        $additionalSql = $this->getAdditionalTransactionSpecificFilters($filter);
        $limit = 4;

        if (isset($filter['limit'])) {
            $limit = $filter['limit'];
        }

        $sql = "SELECT 
                  transaction_items.product_name,
                  transaction_items.product_size AS 'size',
                  transaction_items.product_metrics AS 'metrics',
                  SUM(transaction_items.quantity) as 'sum'
                FROM
                  transaction_items 
                  INNER JOIN transactions 
                    ON transactions.id = transaction_items.`transaction_id` 
                  INNER JOIN transaction_types 
                    ON transaction_types.`id` = transactions.`transaction_type_id` 
                WHERE transactions.`status` = '{$activeFlag}' 
                  AND transaction_types.`code` = '{$saleFlag}' 
                  {$additionalSql}
                GROUP BY 
                transaction_items.product_variation_id, 
                transaction_items.product_size, 
                transaction_items.product_metrics,
                transaction_items.product_name
                ORDER BY SUM(transaction_items.quantity) DESC 
                LIMIT {$limit} ";

        $topItems = DB::select($sql);

        return $topItems;

    }

    public function getTotalCostOfSales($filter)
    {
        $saleFlag = StatusHelper::SALE;
        $activeFlag = StatusHelper::ACTIVE;

        $sql = "SELECT 
                  CASE
                    WHEN SUM(
                      transaction_items.`quantity` * transaction_items.product_cost_price
                    ) IS NULL 
                    THEN 0 
                    ELSE SUM(
                      transaction_items.`quantity` * transaction_items.product_cost_price
                    ) 
                  END AS total_cost_of_sales 
                FROM
                  transaction_items 
                  INNER JOIN transactions 
                    ON transactions.`id` = transaction_items.`transaction_id` 
                  INNER JOIN transaction_types 
                    ON transaction_types.`id` = transactions.`transaction_type_id` 
                WHERE transaction_types.`code` = '{$saleFlag}' 
                  AND transactions.status = '{$activeFlag}' ";

        $sql .= $this->getAdditionalTransactionSpecificFilters($filter);

        $totalCostOfSales = DB::select($sql)[0]->total_cost_of_sales;

        return $totalCostOfSales;
    }

    public function getTotalReturnSales($filter)
    {
        $returnSaleFlag = StatusHelper::RETURN_SALE;
        $activeFlag = StatusHelper::ACTIVE;

        $sql = "SELECT 
                  CASE
                    WHEN SUM(transaction_items.`quantity` * transaction_items.`product_franchisee_price`) IS NULL AND transactions.sub_type = 'delivery'
                    THEN 0 
                    WHEN SUM(transaction_items.`quantity` * transaction_items.`product_selling_price`) IS NULL
                    THEN 0 
                    WHEN transactions.sub_type = 'delivery'
                    THEN SUM(transaction_items.`quantity` * transaction_items.`product_franchisee_price`)
                    ELSE SUM(transaction_items.`quantity` * transaction_items.`product_selling_price`)
                  END AS total_return_sales 
                FROM
                  transaction_items 
                  INNER JOIN transactions 
                    ON transactions.`id` = transaction_items.`transaction_id` 
                  INNER JOIN transaction_types 
                    ON transaction_types.`id` = transactions.`transaction_type_id` 
                WHERE transaction_types.`code` = '{$returnSaleFlag}' 
                  AND transactions.status = '{$activeFlag}' ";

        $sql .= $this->getAdditionalTransactionSpecificFilters($filter);

        $totalCostOfSales = DB::select($sql)[0]->total_return_sales;

        return $totalCostOfSales;
    }

    public function getTotalDiscounts($filter)
    {

        $saleFlag = StatusHelper::SALE;
        $activeFlag = StatusHelper::ACTIVE;

        $sql = "SELECT 
                  CASE
                    WHEN SUM(transactions.discount) IS NULL 
                    THEN 0 
                    ELSE SUM(transactions.discount) 
                  END AS total_discount 
                FROM
                  transactions 
                  INNER JOIN transaction_types 
                    ON transaction_types.id = transactions.`transaction_type_id` 
                WHERE transaction_types.`code` = '{$saleFlag}' 
                  AND transactions.status = '{$activeFlag}' ";

        $sql .= $this->getAdditionalTransactionSpecificFilters($filter);

        $totalDiscount = DB::select($sql)[0]->total_discount;

        return $totalDiscount;
    }

    public function getTotalNetSales($filter)
    {
        $revenue = $this->getTotalSales($filter);
        $returnSales = $this->getTotalReturnSales($filter);
        $discounts = $this->getTotalDiscounts($filter);

        $netSales = $revenue - $returnSales - $discounts;

        return $netSales;
    }

    public function getGrossProfit($filter)
    {
        $revenue = $this->getTotalSales($filter);
        $costOfSales = $this->getTotalCostOfSales($filter);

        $grossProfit = $revenue - $costOfSales;

        return $grossProfit;
    }

    public function getGrossProfitMargin($filter)
    {
        $revenue = $this->getTotalSales($filter);
        $costOfSales = $this->getTotalCostOfSales($filter);

        $margin = ($revenue - $costOfSales) / $revenue;

        return $margin;
    }

    public function getProductsSalesSummary($filter)
    {
        $deliveryFlag = StatusHelper::SUB_TYPE_DELIVERY;

        $additionalSql = $this->getAdditionalTransactionSpecificFilters($filter);

        $branchSql = "";

        if (isset($filter['branch_id'])) {
            $branchId = $filter['branch_id'];
            $branchSql = " AND transactions.branch_id = {$branchId} ";
        }

        $sql = "SELECT all_products_summary.* FROM (SELECT 
                  summary.*,
                  (
                    summary.sales - summary.returns - summary.discounts
                  ) AS net_sales,
                  (
                    summary.sales - summary.cost_of_sales
                  ) AS gross_profit,
                  CASE WHEN (
                    (
                      summary.sales - summary.cost_of_sales
                    ) / summary.sales
                  ) IS NULL THEN 0 ELSE (
                    (
                      summary.sales - summary.cost_of_sales
                    ) / summary.sales
                  ) END AS gross_margin 
                FROM
                  (SELECT 
                    CONCAT(
                      transaction_items.`product_name`,
                      ' (',
                      ROUND(
                        transaction_items.`product_size`
                      ),
                      ' ',
                      transaction_items.`product_metrics`,
                      ')'
                    ) AS product_name,
                    (SELECT 
                      CASE
                        WHEN SUM(
                          t_item.`quantity`
                        ) IS NULL 
                        THEN 0 
                        ELSE SUM(
                          t_item.`quantity`
                        ) 
                      END 
                    FROM
                      transaction_items t_item 
                      INNER JOIN transactions 
                        ON transactions.id = t_item.`transaction_id` 
                        {$additionalSql}
                      INNER JOIN transaction_types 
                        ON transaction_types.id = transactions.`transaction_type_id` 
                    WHERE t_item.product_variation_id = transaction_items.`product_variation_id` 
                      AND transaction_types.code = 'sale' 
                      AND transactions.status = 'active') AS quantity,
                    (SELECT 
                      CASE
                        WHEN SUM(t_item.`quantity` * t_item.`product_franchisee_price`) IS NULL AND transactions.sub_type = 'delivery'
                        THEN 0 
                        WHEN SUM(t_item.`quantity` * t_item.`product_selling_price`) IS NULL
                        THEN 0 
                        WHEN transactions.sub_type = 'delivery'
                        THEN ROUND(SUM(t_item.`quantity` * t_item.`product_franchisee_price`),2)
                        ELSE ROUND(SUM(t_item.`quantity` * t_item.`product_selling_price`),2)
                      END
                    FROM
                      transaction_items t_item 
                      INNER JOIN transactions 
                        ON transactions.id = t_item.`transaction_id` 
                        {$additionalSql}
                      INNER JOIN transaction_types 
                        ON transaction_types.id = transactions.`transaction_type_id` 
                    WHERE t_item.product_variation_id = transaction_items.`product_variation_id` 
                      AND transaction_types.code = 'sale' 
                      AND transactions.status = 'active') AS sales,
                    (SELECT 
                      CASE
                        WHEN SUM(
                          t_item.`quantity` * t_item.`product_cost_price`
                        ) IS NULL 
                        THEN 0 
                        ELSE SUM(
                          t_item.`quantity` * t_item.`product_cost_price`
                        ) 
                      END 
                    FROM
                      transaction_items t_item 
                      INNER JOIN transactions 
                        ON transactions.id = t_item.`transaction_id` 
                        {$additionalSql}
                      INNER JOIN transaction_types 
                        ON transaction_types.id = transactions.`transaction_type_id` 
                    WHERE t_item.product_variation_id = transaction_items.`product_variation_id` 
                      AND transaction_types.code = 'sale' 
                      AND transactions.status = 'active') AS cost_of_sales,
                    (SELECT 
                      CASE
                        WHEN SUM(t_item.`quantity` * t_item.`product_franchisee_price`) IS NULL AND transactions.sub_type = 'delivery'
                        THEN 0 
                        WHEN SUM(t_item.`quantity` * t_item.`product_selling_price`) IS NULL
                        THEN 0 
                        WHEN transactions.sub_type = 'delivery'
                        THEN ROUND(SUM(t_item.`quantity` * t_item.`product_franchisee_price`),2)
                        ELSE ROUND(SUM(t_item.`quantity` * t_item.`product_selling_price`),2)
                      END
                    FROM
                      transaction_items t_item 
                      INNER JOIN transactions 
                        ON transactions.id = t_item.`transaction_id` 
                        {$additionalSql}
                      INNER JOIN transaction_types 
                        ON transaction_types.id = transactions.`transaction_type_id` 
                    WHERE t_item.product_variation_id = transaction_items.`product_variation_id` 
                      AND transaction_types.code = 'return_sale' 
                      AND transactions.status = 'active') AS 'returns',
                      (SELECT 
                      CASE
                        WHEN SUM(
                          t_item.product_discount
                        ) IS NULL 
                        THEN 0 
                        ELSE SUM(
                          t_item.product_discount
                        ) 
                      END 
                    FROM
                      transaction_items t_item 
                      INNER JOIN transactions 
                        ON transactions.id = t_item.`transaction_id` 
                        {$additionalSql}
                      INNER JOIN transaction_types 
                        ON transaction_types.id = transactions.`transaction_type_id` 
                    WHERE t_item.product_variation_id = transaction_items.`product_variation_id` 
                      AND transaction_types.code = 'sale' 
                      AND transactions.status = 'active') AS 'discounts',
                    (SELECT 
                      CASE
                        WHEN SUM(transactions.`discount`) IS NULL 
                        THEN 0 
                        ELSE SUM(transactions.`discount`) 
                      END 
                    FROM
                      transaction_items t_item 
                      INNER JOIN transactions 
                        ON transactions.id = t_item.`transaction_id` 
                        {$additionalSql}
                      INNER JOIN transaction_types 
                        ON transaction_types.id = transactions.`transaction_type_id` 
                    WHERE t_item.product_variation_id = transaction_items.`product_variation_id` 
                      AND transaction_types.code = 'sale' 
                      AND transactions.status = 'active' ) AS total_discounts 
                  FROM
                    transaction_items 
                  GROUP BY 
                  transaction_items.product_variation_id
                  ) AS summary) AS all_products_summary WHERE all_products_summary.quantity > 0 ";

        $productsSummary = DB::select($sql);

        return $productsSummary;

    }

    public function getAllProductsSalesSummary($filter)
    {
        $deliveryFlag = StatusHelper::SUB_TYPE_DELIVERY;

        $additionalSql = $this->getAdditionalTransactionSpecificFilters($filter);


        $sql = "SELECT 
                  summary.*,
                  (
                    summary.sales - summary.returns - summary.discounts
                  ) AS net_sales,
                  (
                    summary.sales - summary.cost_of_sales
                  ) AS gross_profit,
                  CASE
                    WHEN (
                      (
                        summary.sales - summary.cost_of_sales
                      ) / summary.sales
                    ) IS NULL 
                    THEN 0 
                    ELSE (
                      (
                        summary.sales - summary.cost_of_sales
                      ) / summary.sales
                    ) 
                  END AS gross_margin 
                FROM
                  (SELECT 
                    CONCAT(
                      products.`name`,
                      ' (',
                      ROUND(product_variations.`size`),
                      ' ',
                      product_variations.`metrics`,
                      ')'
                    ) AS product_name,
                    (SELECT 
                      CASE
                        WHEN SUM(t_item.`quantity`) IS NULL 
                        THEN 0 
                        ELSE SUM(t_item.`quantity`) 
                      END 
                    FROM
                      transaction_items t_item 
                      INNER JOIN transactions 
                        ON transactions.id = t_item.`transaction_id` 
                        {$additionalSql}
                      INNER JOIN transaction_types 
                        ON transaction_types.id = transactions.`transaction_type_id` 
                    WHERE t_item.product_variation_id = transaction_items.`product_variation_id` 
                      AND transaction_types.code = 'sale' 
                      AND transactions.status = 'active') AS quantity,
                    (SELECT 
                      CASE
                        WHEN SUM(t_item.`quantity` * t_item.`product_franchisee_price`) IS NULL AND transactions.sub_type = 'delivery'
                        THEN 0 
                        WHEN SUM(t_item.`quantity` * t_item.`product_selling_price`) IS NULL
                        THEN 0 
                        WHEN transactions.sub_type = 'delivery'
                        THEN ROUND(SUM(t_item.`quantity` * t_item.`product_franchisee_price`),2)
                        ELSE ROUND(SUM(t_item.`quantity` * t_item.`product_selling_price`),2)
                      END
                    FROM
                      transaction_items t_item 
                      INNER JOIN transactions 
                        ON transactions.id = t_item.`transaction_id` 
                        {$additionalSql}
                      INNER JOIN transaction_types 
                        ON transaction_types.id = transactions.`transaction_type_id` 
                    WHERE t_item.product_variation_id = transaction_items.`product_variation_id` 
                      AND transaction_types.code = 'sale' 
                      AND transactions.status = 'active') AS sales,
                    (SELECT 
                      CASE
                        WHEN SUM(
                          t_item.`quantity` * t_item.`product_cost_price`
                        ) IS NULL 
                        THEN 0 
                        ELSE SUM(
                          t_item.`quantity` * t_item.`product_cost_price`
                        ) 
                      END 
                    FROM
                      transaction_items t_item 
                      INNER JOIN transactions 
                        ON transactions.id = t_item.`transaction_id` 
                        {$additionalSql}
                      INNER JOIN transaction_types 
                        ON transaction_types.id = transactions.`transaction_type_id` 
                    WHERE t_item.product_variation_id = transaction_items.`product_variation_id` 
                      AND transaction_types.code = 'sale' 
                      AND transactions.status = 'active') AS cost_of_sales,
                    (SELECT 
                      CASE
                        WHEN SUM(t_item.`quantity` * t_item.`product_franchisee_price`) IS NULL AND transactions.sub_type = 'delivery'
                        THEN 0 
                        WHEN SUM(t_item.`quantity` * t_item.`product_selling_price`) IS NULL
                        THEN 0 
                        WHEN transactions.sub_type = 'delivery'
                        THEN ROUND(SUM(t_item.`quantity` * t_item.`product_franchisee_price`),2)
                        ELSE ROUND(SUM(t_item.`quantity` * t_item.`product_selling_price`),2)
                      END
                    FROM
                      transaction_items t_item 
                      INNER JOIN transactions 
                        ON transactions.id = t_item.`transaction_id` 
                        {$additionalSql}
                      INNER JOIN transaction_types 
                        ON transaction_types.id = transactions.`transaction_type_id` 
                    WHERE t_item.product_variation_id = transaction_items.`product_variation_id` 
                      AND transaction_types.code = 'return_sale' 
                      AND transactions.status = 'active') AS 'returns',
                      (SELECT 
                      CASE
                        WHEN SUM(
                          t_item.product_discount
                        ) IS NULL 
                        THEN 0 
                        ELSE SUM(
                          t_item.product_discount
                        ) 
                      END 
                    FROM
                      transaction_items t_item 
                      INNER JOIN transactions 
                        ON transactions.id = t_item.`transaction_id` 
                        {$additionalSql}
                      INNER JOIN transaction_types 
                        ON transaction_types.id = transactions.`transaction_type_id` 
                    WHERE t_item.product_variation_id = transaction_items.`product_variation_id` 
                      AND transaction_types.code = 'sale' 
                      AND transactions.status = 'active') AS 'discounts',
                    (SELECT 
                      CASE
                        WHEN SUM(transactions.`discount`) IS NULL 
                        THEN 0 
                        ELSE SUM(transactions.`discount`) 
                      END 
                    FROM
                      transaction_items t_item 
                      INNER JOIN transactions 
                        ON transactions.id = t_item.`transaction_id` 
                        {$additionalSql}
                      INNER JOIN transaction_types 
                        ON transaction_types.id = transactions.`transaction_type_id` 
                    WHERE t_item.product_variation_id = transaction_items.`product_variation_id` 
                      AND transaction_types.code = 'sale' 
                      AND transactions.status = 'active') AS total_discounts 
                  FROM
                    product_variations 
                    INNER JOIN products 
                      ON products.id = product_variations.`product_id` 
                    LEFT JOIN transaction_items 
                      ON transaction_items.`product_variation_id` = product_variations.id 
                    LEFT JOIN transactions 
                      ON transactions.`id` = transaction_items.`id`
                  WHERE product_variations.id IS NOT NULL 
                  GROUP BY product_variations.id) AS summary ";

        $productsSummary = DB::select($sql);

        return $productsSummary;

    }

    public function getTransactionDailySales($filter)
    {
        $additionalSql = $this->getAdditionalTransactionSpecificFilters($filter);

        $sql = "SELECT
                  summary.*,
                  CASE WHEN summary.weekday = 0 THEN 'M'
                    WHEN summary.weekday = 1 THEN 'T'
                    WHEN summary.weekday = 2 THEN 'W'
                    WHEN summary.weekday = 3 THEN 'T'
                    WHEN summary.weekday = 4 THEN 'F'
                    WHEN summary.weekday = 5 THEN 'S'
                    WHEN summary.weekday = 6 THEN 'S'
                    END as 'weekday_code',
                    CASE WHEN summary.weekday = 0 THEN 'Monday'
                    WHEN summary.weekday = 1 THEN 'Tuesday'
                    WHEN summary.weekday = 2 THEN 'Wednesday'
                    WHEN summary.weekday = 3 THEN 'Thursday'
                    WHEN summary.weekday = 4 THEN 'Friday'
                    WHEN summary.weekday = 5 THEN 'Saturday'
                    WHEN summary.weekday = 6 THEN 'Sunday'
                    END as 'weekday_string',
                  (
                    summary.sales - summary.returns - summary.discounts
                  ) AS net_sales,
                  (
                    summary.sales - summary.cost_of_sales
                  ) AS gross_profit,
                  CASE
                    WHEN (
                      (
                        summary.sales - summary.cost_of_sales
                      ) / summary.sales
                    ) IS NULL 
                    THEN 0 
                    ELSE (
                      (
                        summary.sales - summary.cost_of_sales
                      ) / summary.sales
                    ) 
                  END AS gross_margin 
                FROM
                  (SELECT 
                    transactions.`created_at`,
                    DATE(transactions.`created_at`) AS 'date',
                    WEEKDAY(DATE(transactions.`created_at`)) AS 'weekday',
                    (SELECT 
                      CASE
                        WHEN SUM(
                          t_item.`quantity` * t_item.`product_selling_price`
                        ) IS NULL 
                        THEN 0 
                        ELSE SUM(
                          t_item.`quantity` * t_item.`product_selling_price`
                        ) 
                      END 
                    FROM
                      transaction_items t_item 
                      INNER JOIN transactions t
                        ON t.id = t_item.`transaction_id` 
                      INNER JOIN transaction_types 
                        ON transaction_types.id = t.`transaction_type_id` 
                    WHERE DATE(t.`created_at`) = DATE(transactions.created_at) 
                      AND transaction_types.code = 'sale' 
                      AND t.status = 'active') AS 'sales',
                    (SELECT 
                      CASE
                        WHEN SUM(
                          t_item.`quantity` * t_item.`product_cost_price`
                        ) IS NULL 
                        THEN 0 
                        ELSE SUM(
                          t_item.`quantity` * t_item.`product_cost_price`
                        ) 
                      END 
                    FROM
                      transaction_items t_item 
                      INNER JOIN transactions t
                        ON t.id = t_item.`transaction_id` 
                      INNER JOIN transaction_types 
                        ON transaction_types.id = t.`transaction_type_id` 
                    WHERE DATE(t.`created_at`) = DATE(transactions.created_at) 
                      AND transaction_types.code = 'sale' 
                      AND t.status = 'active') AS 'cost_of_sales',
                    (SELECT 
                      CASE
                        WHEN SUM(
                          t_item.`quantity` * t_item.`product_selling_price`
                        ) IS NULL 
                        THEN 0 
                        ELSE SUM(
                          t_item.`quantity` * t_item.`product_selling_price`
                        ) 
                      END 
                    FROM
                      transaction_items t_item 
                      INNER JOIN transactions t
                        ON t.id = t_item.`transaction_id` 
                      INNER JOIN transaction_types 
                        ON transaction_types.id = t.`transaction_type_id` 
                    WHERE DATE(t.`created_at`) = DATE(transactions.created_at) 
                      AND transaction_types.code = 'return_sale' 
                      AND t.status = 'active') AS 'returns',
                    (SELECT 
                      CASE
                        WHEN SUM(t.`discount`) IS NULL 
                        THEN 0 
                        ELSE SUM(t.`discount`) 
                      END 
                    FROM
                      transaction_items t_item 
                      INNER JOIN transactions t
                        ON t.id = t_item.`transaction_id` 
                      INNER JOIN transaction_types 
                        ON transaction_types.id = t.`transaction_type_id` 
                    WHERE DATE(t.`created_at`) = DATE(transactions.`created_at`) 
                      AND transaction_types.code = 'sale' 
                      AND t.status = 'active') AS discounts 
                  FROM
                    transactions
                    WHERE transactions.id IS NOT NULL
                    {$additionalSql}
                  GROUP BY DATE(transactions.`created_at`) )
                  AS summary ";

        $dailySalesSummary = DB::select($sql);

        return $dailySalesSummary;
    }

    public function getDailySales($filter)
    {
        $deliveryFlag = StatusHelper::SUB_TYPE_DELIVERY;

        $branchSql = "";
        $subTypeSql = " AND (t.sub_type IN('{$deliveryFlag}') OR t.`sub_type` IS NULL) ";

        if (isset($filter['branch_id'])) {
            $branchId = $filter['branch_id'];
            $branchSql = " AND t.branch_id = {$branchId} ";
        }

        if (isset($filter['sub_type'])) {
            $subType = $filter['sub_type'];
            $subTypeSql = " AND t.sub_type IN('{$subType}') ";
        }

        $fromToDetails = $this->getFromAndToByFilter($filter, 't');

        $from = $fromToDetails['from'];
        $to = $fromToDetails['to'];

        $fromSql = $fromToDetails['from_sql'];
        $toSql = $fromToDetails['to_sql'];

        $sql = "SELECT 
                  summary.*,
                  CASE
                    WHEN summary.weekday = 0 
                    THEN 'M' 
                    WHEN summary.weekday = 1 
                    THEN 'T' 
                    WHEN summary.weekday = 2 
                    THEN 'W' 
                    WHEN summary.weekday = 3 
                    THEN 'T' 
                    WHEN summary.weekday = 4 
                    THEN 'F' 
                    WHEN summary.weekday = 5 
                    THEN 'S' 
                    WHEN summary.weekday = 6 
                    THEN 'S' 
                  END AS 'weekday_code',
                  CASE
                    WHEN summary.weekday = 0 
                    THEN 'Monday' 
                    WHEN summary.weekday = 1 
                    THEN 'Tuesday' 
                    WHEN summary.weekday = 2 
                    THEN 'Wednesday' 
                    WHEN summary.weekday = 3 
                    THEN 'Thursday' 
                    WHEN summary.weekday = 4 
                    THEN 'Friday' 
                    WHEN summary.weekday = 5 
                    THEN 'Saturday' 
                    WHEN summary.weekday = 6 
                    THEN 'Sunday' 
                  END AS 'weekday_string',
                  (
                    summary.sales - summary.returns - summary.discounts
                  ) AS net_sales,
                  (
                    summary.sales - summary.cost_of_sales
                  ) AS gross_profit,
                  CASE
                    WHEN (
                      (
                        summary.sales - summary.cost_of_sales
                      ) / summary.sales
                    ) IS NULL 
                    THEN 0 
                    ELSE (
                      (
                        summary.sales - summary.cost_of_sales
                      ) / summary.sales
                    ) 
                  END AS gross_margin 
                FROM
                  (SELECT 
                    date_query.date,
                    WEEKDAY(date_query.date) AS 'weekday',
                    
                    
                    (SELECT 
                      CASE
                        WHEN SUM(
                          t_item.`quantity` * t_item.`product_selling_price`
                        ) IS NULL 
                        THEN 0 
                        ELSE SUM(
                          t_item.`quantity` * t_item.`product_selling_price`
                        ) 
                      END 
                    FROM
                      transaction_items t_item 
                      INNER JOIN transactions t 
                        ON t.id = t_item.`transaction_id` 
                        {$branchSql}
                        {$subTypeSql}
                      INNER JOIN transaction_types 
                        ON transaction_types.id = t.`transaction_type_id` 
                    WHERE DATE(t.`created_at`) = DATE(transactions.created_at) 
                      AND transaction_types.code = 'sale' 
                      AND t.status = 'active' ) AS 'sales',
                      
                      
                    (SELECT 
                      CASE
                        WHEN SUM(
                          t_item.`quantity` * t_item.`product_cost_price`
                        ) IS NULL 
                        THEN 0 
                        ELSE SUM(
                          t_item.`quantity` * t_item.`product_cost_price`
                        ) 
                      END 
                    FROM
                      transaction_items t_item 
                      INNER JOIN transactions t 
                        ON t.id = t_item.`transaction_id` 
                        {$branchSql}
                        {$subTypeSql}
                      INNER JOIN transaction_types 
                        ON transaction_types.id = t.`transaction_type_id` 
                    WHERE DATE(t.`created_at`) = DATE(transactions.created_at) 
                      AND transaction_types.code = 'sale' 
                      AND t.status = 'active' ) AS 'cost_of_sales',
                      
                      
                    (SELECT 
                      CASE
                        WHEN SUM(
                          t_item.`quantity` * t_item.`product_selling_price`
                        ) IS NULL 
                        THEN 0 
                        ELSE SUM(
                          t_item.`quantity` * t_item.`product_selling_price`
                        ) 
                      END 
                    FROM
                      transaction_items t_item 
                      INNER JOIN transactions t 
                        ON t.id = t_item.`transaction_id` 
                        {$branchSql}
                        {$subTypeSql}
                      INNER JOIN transaction_types 
                        ON transaction_types.id = t.`transaction_type_id` 
                    WHERE DATE(t.`created_at`) = DATE(transactions.created_at) 
                      AND transaction_types.code = 'return_sale' 
                      AND t.status = 'active' ) AS 'returns',
                      
                      
                    
                    (SELECT 
                  CASE
                    WHEN SUM(t.discount) IS NULL 
                    THEN 0 
                    ELSE SUM(t.discount) 
                  END AS total_discount 
                FROM
                  transactions t
                  INNER JOIN transaction_types 
                    ON transaction_types.id = t.`transaction_type_id` 
                WHERE transaction_types.`code` = 'sale' 
                  AND t.status = 'active'
                  AND DATE(t.`created_at`) = DATE(transactions.created_at)
                   {$branchSql}
                  {$subTypeSql}
                  ) AS 'discounts'
                  
                  
                
                FROM
                
                (SELECT ADDDATE('{$from}', INTERVAL @i:=@i+1 DAY) AS 'date'
                FROM (
                SELECT a.a
                FROM (SELECT 0 AS a UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) AS a
                CROSS JOIN (SELECT 0 AS a UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) AS b
                CROSS JOIN (SELECT 0 AS a UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) AS c
                ) a
                JOIN (SELECT @i := -1) r1
                WHERE 
                @i < DATEDIFF('{$to}', '{$from}')) AS date_query 
                
                LEFT JOIN transactions ON DATE(transactions.`created_at`) = date_query.date GROUP BY date_query.date) AS summary";

        $dailySalesSummary = DB::select($sql);

        return $dailySalesSummary;
    }

    private function getAdditionalTransactionSpecificFilters($filter)
    {
        $deliveryFlag = StatusHelper::SUB_TYPE_DELIVERY;

        $branchSql = "";
        $subTypeSql = " AND (transactions.sub_type IN('delivery') OR transactions.`sub_type` IS NULL) ";

        if (isset($filter['branch_id'])) {
            $branchId = $filter['branch_id'];
            $branchSql = " AND transactions.branch_id = {$branchId} ";
        }

        if (isset($filter['sub_type'])) {
            $subType = $filter['sub_type'];
            $subTypeSql = " AND transactions.sub_type IN('{$subType}') ";
        }

        $fromToDetails = $this->getFromAndToByFilter($filter);

        $fromSql = $fromToDetails['from_sql'];
        $toSql = $fromToDetails['to_sql'];


        return $branchSql . $subTypeSql . $fromSql . $toSql;
    }

    private function getFromAndToByFilter($filter, $columnPrefix = 'transactions')
    {

        $from = "";
        $to = "";
        $fromSql = "";
        $toSql = "";

        if (isset($filter['from'])) {
            $from = $filter['from'];
            $fromSql = " AND DATE({$columnPrefix}.created_at) >= DATE('{$from}') ";
        }

        if (isset($filter['to'])) {
            $to = $filter['to'];
            $toSql = " AND DATE({$columnPrefix}.created_at) <= DATE('{$to}') ";
        }

        if (isset($filter['range'])) {

            $range = $filter['range'];

            $fromToDetails = $this->getFromAndToByRange($range, $columnPrefix, $filter);

            $from = $fromToDetails['from'];
            $to = $fromToDetails['to'];
            $fromSql = $fromToDetails['from_sql'];
            $toSql = $fromToDetails['to_sql'];
        }

        return [
            'from' => $from,
            'to' => $to,
            'from_sql' => $fromSql,
            'to_sql' => $toSql
        ];

    }

    private function getFromAndToByRange($range, $columnPrefix = 'transactions', $filter = [])
    {
        $now = Carbon::now();
        $from = $now->startOfDay()->toDateTimeString();
        $to = $now->endOfDay()->toDateTimeString();

        $filterPreviousDate = 0;

        if (isset($filter['previous_date'])) {
            $filterPreviousDate = $filter['previous_date'];
        }

        if ($filterPreviousDate) {

            switch ($range) {
                case 'day':
                case 'daily':
                    $range = 'yesterday';
                    break;
                case 'week':
                case 'weekly':
                    $range = 'last_week';
                    break;
                case 'month':
                case 'monthly':
                    $range = 'last_month';
                    break;
                case 'year':
                case 'yearly':
                    $range = 'last_year';
                    break;
            }

        }

        switch ($range) {
            case 'yesterday':
            case 'last_day':
                $yesterday = $now->subDay();
                $from = $yesterday->StartOfDay()->toDateTimeString();
                $to = $yesterday->endOfDay()->toDateTimeString();
                break;
            case 'last_week':
                $lastWeek = $now->subWeek();
                $from = $lastWeek->startOfWeek()->toDateTimeString();
                $to = $lastWeek->endOfWeek()->toDateTimeString();
                break;
            case 'last_month':
                $lastMonth = $now->subMonth();
                $from = $lastMonth->startOfMonth()->toDateTimeString();
                $to = $lastMonth->endOfMonth()->toDateTimeString();
                break;
            case 'last_year':
                $lastYear = $now->subYear();
                $from = $lastYear->startOfYear()->toDateTimeString();
                $to = $lastYear->endOfYear()->toDateTimeString();
                break;
            case 'last_10_days':
                $from = Carbon::now()->subDays(10)->toDateTimeString();
                $to = $now->toDateTimeString();
                break;
            case 'day':
            case 'daily':
                $from = $now->startOfDay()->toDateTimeString();
                $to = $now->endOfDay()->toDateTimeString();
                break;
            case 'week':
            case 'weekly':
                $from = $now->startOfWeek()->toDateTimeString();
                $to = $now->endOfWeek()->toDateTimeString();
                break;
            case 'month':
            case 'monthly':
                $from = $now->startOfMonth()->toDateTimeString();
                $to = $now->endOfMonth()->toDateTimeString();
                break;
            case 'year':
            case 'yearly':
                $from = $now->startOfYear()->toDateTimeString();
                $to = $now->endOfYear()->toDateTimeString();
                break;
        }

        $fromSql = " AND DATE({$columnPrefix}.`created_at`) >= DATE('{$from}') ";
        $toSql = " AND DATE({$columnPrefix}.`created_at`) <= DATE('{$to}') ";

        return [
            'from' => $from,
            'to' => $to,
            'from_sql' => $fromSql,
            'to_sql' => $toSql
        ];
    }

    private function getAdditionalSqlFilters($filter)
    {
        $branchSql = "";
        $querySql = "";
        $transactionTypeSql = "";
        $excludeVoidSql = "";
        $productVariationSql = "";

        $voidFlag = StatusHelper::VOID;

        $voidSaleFlag = StatusHelper::VOID_SALE;
        $returnFlag = StatusHelper::RETURN_SALE;
        $voidReturnFlag = StatusHelper::VOID_RETURN;
        $shortFlag = StatusHelper::SHORT_SALE;
        $shortoverFlag = StatusHelper::SHORTOVER_SALE;
        $voidShortFlag = StatusHelper::VOID_SHORT;
        $voidShortoverFlag = StatusHelper::VOID_SHORTOVER;
        $deliveryFlag = StatusHelper::SUB_TYPE_DELIVERY;

        $orderSql = "";
        $sortSql = "";
        $fromSql = "";
        $toSql = "";
        $staffSql = "";
        $groupSql = "";
        $excludeDeliverySaleSql = "";
        $subTypeSql = "";

        $excludeVoid = 0;
        $forTrackingOnly = 0;
        $excludeDelivery = 0;

        $fromToDetails = $this->getFromAndToByFilter($filter);

        $from = $fromToDetails['from'];
        $to = $fromToDetails['to'];

        if ($from != "") {
            $fromSql = " AND DATE(transaction_query.created_at) >= DATE('{$from}') ";
        }

        if ($to != "") {
            $toSql = " AND DATE(transaction_query.created_at) <= DATE('{$to}') ";
        }

        if (isset($filter['branch_id'])) {

            $branchId = $filter['branch_id'];
            $branchSql = " AND transaction_query.branch_id = {$branchId} ";

            if ($branchId == null || $branchId == 'null') {
                $branchSql = " AND transaction_query.branch_id IS NULL ";
            }

        }

        if (isset($filter['transaction_type'])) {
            $transactionTypeCode = strtolower($filter['transaction_type']);
            $transactionTypeSql = " AND transaction_query.transaction_type = '{$transactionTypeCode}' ";
        }

        if (isset($filter['staff_id'])) {
            $staffId = $filter['staff_id'];
            $staffSql = " AND transaction_query.staff_id = '{$staffId}' ";
        }

        if (isset($filter['exclude_void'])) {
            $excludeVoid = $filter['exclude_void'];
        }

        if (isset($filter['tracking'])) {
            $forTrackingOnly = $filter['tracking'];
        }

        if ($excludeVoid) {
            $excludeVoidSql = " AND transaction_query.status NOT IN('{$voidFlag}') ";
        }

        if ($forTrackingOnly) {
            $excludeVoidSql = " AND transaction_query.transaction_type 
            NOT IN('{$voidSaleFlag}','{$returnFlag}','{$voidReturnFlag}','{$shortFlag}','{$shortoverFlag}',
            '{$voidShortFlag}','{$voidShortoverFlag}') ";
        }

        if (isset($filter['group'])) {
            $group = $filter['group'];
            $groupSql = " AND transaction_query.group = '{$group}' ";
        }

        if (isset($filter['order'])) {

            $order = strtoupper($filter['order']);

            if ($order == 'ASC') {
                $orderSql = 'ASC';
            }

            if ($order == 'DESC' || empty($order) || trim($order) == "") {
                $orderSql = 'DESC';
            }

        }

        if (!isset($filter['order'])) {
            $orderSql = 'DESC';
        }

        if (isset($filter['sort'])) {

            $sort = $filter['sort'];

            switch ($sort) {
                case 'or_number':
                    $sortSql = " ORDER BY transaction_query.or_no {$orderSql} ";
                    break;

                case 'date':
                    $sortSql = " ORDER BY transaction_query.created_at {$orderSql} ";
                    break;

                case 'date_id':
                    $sortSql = " ORDER BY transaction_query.created_at {$orderSql}, transaction_query.id {$orderSql} ";
                    break;

                case 'id':
                    $sortSql = " ORDER BY transaction_query.id {$orderSql} ";
                    break;

                default:
                    $sortSql = " ORDER BY transaction_query.or_no {$orderSql} ";
            }
        }

        if (!isset($filter['sort'])) {
            $sortSql = " ORDER BY transaction_query.created_at {$orderSql} ";
        }

        if (isset($filter['q'])) {
            $query = $filter['q'];
//            $querySql = " AND transaction_query.query_string LIKE '%{$query}%' ";
            $querySql = " AND LOWER(
                CONCAT(
                    transaction_query.or_no,
                    ' ',
                    ISNULL(transaction_query.customer_firstname),
                    ' ',
                    ISNULL(transaction_query.customer_lastname),
                    ' ',
                    transaction_query.staff_firstname,
                    ' ',
                    transaction_query.staff_lastname,
                    ' ',
                    transaction_query.transaction_type_name,
                    ' ',
                    transaction_query.branch_name,
                    ' '
                )
            ) 
            LIKE LOWER('%{$query}%') ";
        }

        if (isset($filter['exclude_delivery'])) {
            $excludeDelivery = $filter['exclude_delivery'];
        }

        if ($excludeDelivery) {
            $excludeDeliverySaleSql = " AND (transaction_query.sub_type NOT IN('{$deliveryFlag}') OR transaction_query.sub_type IS NULL) ";
        }

        if (isset($filter['sub_type'])) {
            $subType = $filter['sub_type'];
            $subTypeSql = " AND (transaction_query.sub_type IN('{$subType}')) ";
        }

        if (isset($filter['product_variation_id'])) {
            $productVariationId = $filter['product_variation_id'];
            $productVariationSql = " AND transaction_query.product_variation_id = {$productVariationId} ";
        }

        $additionalSql = $branchSql . $excludeDeliverySaleSql . $subTypeSql . $staffSql . $groupSql . $productVariationSql . $transactionTypeSql . $excludeVoidSql . $querySql . $fromSql . $toSql . $sortSql;

        return $additionalSql;
    }

    private function getItemsByTransactionId($transactionId)
    {
        $items = TransactionItem::where('transaction_id', $transactionId)
            ->select(
                'transaction_items.*',
                'product_cost_price as cost_price',
                'product_metrics as metrics',
                'product_name as name',
                'product_selling_price as selling_price',
                'product_franchisee_price as franchisee_price',
                'product_size as size',
                'quantity as branch_quantity',
                DB::raw("ROUND(quantity*product_selling_price,2) as subtotal"),
                DB::raw("ROUND(quantity*product_franchisee_price,2) as franchisee_subtotal")
            )
            ->get();

        return $items;
    }

    public function getReturnItemsByOrNumber($orNumber, $branchId = null)
    {
        $transactionTypeCode = StatusHelper::RETURN_SALE;
        $activeFlag = StatusHelper::ACTIVE;

        $items = TransactionItem::where('transaction_items.product_variation_id', '!=', null)
            ->join('transactions', 'transactions.id', '=', 'transaction_items.transaction_id')
            ->join('transaction_types', 'transaction_types.id', '=', 'transactions.transaction_type_id')
            ->where('transaction_types.code', $transactionTypeCode)
            ->where('transactions.or_no', $orNumber)
            ->where('transactions.status', $activeFlag)// means that it is not voided yet
            ->select(
                'transaction_items.*',
                'product_cost_price as cost_price',
                'product_metrics as metrics',
                'product_name as name',
                'product_selling_price as selling_price',
                'product_franchisee_price as franchisee_price',
                'product_size as size',
                'quantity as branch_quantity',
                DB::raw("ROUND(quantity*product_selling_price,2) as subtotal"),
                DB::raw("ROUND(quantity*product_franchisee_price,2) as franchisee_subtotal")
            );

        if($branchId){
            $items = $items->where('transactions.branch_id','=',$branchId);
        }

        $items = $items->get();

        return $items;
    }

    public function getReturnItemsByInvoiceNo($invoiceNo)
    {
        $transactionTypeCode = StatusHelper::RETURN_SALE;
        $activeFlag = StatusHelper::ACTIVE;

        $items = TransactionItem::where('transaction_items.product_variation_id', '!=', null)
            ->join('transactions', 'transactions.id', '=', 'transaction_items.transaction_id')
            ->join('transaction_types', 'transaction_types.id', '=', 'transactions.transaction_type_id')
            ->where('transaction_types.code', $transactionTypeCode)
            ->where('transactions.invoice_no', $invoiceNo)
            ->where('transactions.status', $activeFlag)// means that it is not voided yet
            ->select(
                'transaction_items.*',
                'product_cost_price as cost_price',
                'product_metrics as metrics',
                'product_name as name',
                'product_selling_price as selling_price',
                'product_franchisee_price as franchisee_price',
                'product_size as size',
                'quantity as branch_quantity',
                DB::raw("ROUND(quantity*product_selling_price,2) as subtotal"),
                DB::raw("ROUND(quantity*product_franchisee_price,2) as franchisee_subtotal")
            )
            ->get();

        return $items;
    }

    public function getProductRemainingDeliverySaleQuantityByInvoiceNo($productVariationId, $invoiceNo)
    {
        $saleFlag = StatusHelper::SALE;
        $returnSaleFlag = StatusHelper::RETURN_SALE;
        $activeFlag = StatusHelper::ACTIVE;

        $sql = "SELECT 
                  (
                    count_query.sales_count - count_query.return_sales_count
                  ) AS quantity 
                FROM
                  (SELECT 
                    * 
                  FROM
                    (
                      (SELECT 
                        CASE
                          WHEN SUM(transaction_items.quantity) IS NULL 
                          THEN 0 
                          ELSE SUM(transaction_items.`quantity`) 
                        END AS sales_count 
                      FROM
                        transaction_items 
                        INNER JOIN transactions 
                          ON transactions.id = transaction_items.`transaction_id` 
                        INNER JOIN transaction_types 
                          ON transaction_types.`id` = transactions.transaction_type_id 
                      WHERE transactions.invoice_no = '{$invoiceNo}' 
                        AND transaction_types.`code` = '{$saleFlag}' 
                        AND transactions.`status` = '{$activeFlag}' 
                        AND transaction_items.product_variation_id = {$productVariationId}) AS sales_count,
                      (SELECT 
                        CASE
                          WHEN SUM(transaction_items.quantity) IS NULL 
                          THEN 0 
                          ELSE SUM(transaction_items.`quantity`) 
                        END AS return_sales_count 
                      FROM
                        transaction_items 
                        INNER JOIN transactions 
                          ON transactions.id = transaction_items.`transaction_id` 
                        INNER JOIN transaction_types 
                          ON transaction_types.`id` = transactions.transaction_type_id 
                      WHERE transactions.invoice_no = '{$invoiceNo}' 
                        AND transaction_types.`code` = '{$returnSaleFlag}' 
                        AND transactions.`status` = '{$activeFlag}' 
                        AND transaction_items.product_variation_id = {$productVariationId}) AS return_sales_count
                    )) AS count_query ";

        $counts = DB::select($sql);

        return $counts[0]->quantity;
    }

    public function getBranchesSalesSummary($filter)
    {
        $branchKeysSql = "";
        $additionalSpecificFilters = $this->getAdditionalTransactionSpecificFilters($filter);

        if (isset($filter['keys'])) {
            $branchKeys = explode(',', $filter['keys']);
            $branchKeysSql = " AND branches.`key` IN( '" . implode($branchKeys, "', '") . "' )";
        }

        $sql = "
        SELECT 
          transaction_query.*,
          (
            transaction_query.sales - transaction_query.returns - transaction_query.discounts
          ) AS 'net_sales',
          (
            transaction_query.sales - transaction_query.cost_of_sales
          ) AS 'gross_profit',
          CASE
            WHEN (
              (
                transaction_query.sales - transaction_query.cost_of_sales
              ) / transaction_query.sales
            ) IS NULL 
            THEN 0 
            ELSE (
              (
                transaction_query.sales - transaction_query.cost_of_sales
              ) / transaction_query.sales
            ) 
          END AS 'gross_margin' 
        FROM
          (SELECT 
            branches.`name`,
            branches.`id`,
            branches.`key`,
            branches.`address`,
            branches.`phone`,
            branches.`type`,
            (SELECT 
              CASE
                WHEN SUM(t_item.`quantity` * t_item.`product_franchisee_price`) IS NULL AND transactions.sub_type = 'delivery'
                THEN 0 
                WHEN SUM(t_item.`quantity` * t_item.`product_selling_price`) IS NULL
                THEN 0 
                WHEN transactions.sub_type = 'delivery'
                THEN SUM(t_item.`quantity` * t_item.`product_franchisee_price`)
                ELSE SUM(t_item.`quantity` * t_item.`product_selling_price`)
              END 
            FROM
              transaction_items t_item 
              INNER JOIN transactions 
                ON transactions.id = t_item.`transaction_id` 
              INNER JOIN transaction_types 
                ON transaction_types.id = transactions.`transaction_type_id` 
            WHERE transaction_types.code = 'sale' 
              AND transactions.status = 'active' 
              AND transactions.`branch_id` = branches.`id` {$additionalSpecificFilters}) AS 'sales',
            (SELECT 
              CASE
                WHEN SUM(
                  t_item.`quantity` * t_item.`product_cost_price`
                ) IS NULL 
                THEN 0 
                ELSE SUM(
                  t_item.`quantity` * t_item.`product_cost_price`
                ) 
              END 
            FROM
              transaction_items t_item 
              INNER JOIN transactions 
                ON transactions.id = t_item.`transaction_id` 
              INNER JOIN transaction_types 
                ON transaction_types.id = transactions.`transaction_type_id` 
            WHERE transaction_types.code = 'sale' 
              AND transactions.status = 'active' 
              AND transactions.`branch_id` = branches.`id` {$additionalSpecificFilters}) AS 'cost_of_sales',
            (SELECT 
              CASE
                WHEN SUM(t_item.`quantity` * t_item.`product_franchisee_price`) IS NULL AND transactions.sub_type = 'delivery'
                THEN 0 
                WHEN SUM(t_item.`quantity` * t_item.`product_selling_price`) IS NULL
                THEN 0 
                WHEN transactions.sub_type = 'delivery'
                THEN SUM(t_item.`quantity` * t_item.`product_franchisee_price`)
                ELSE SUM(t_item.`quantity` * t_item.`product_selling_price`)
              END 
            FROM
              transaction_items t_item 
              INNER JOIN transactions 
                ON transactions.id = t_item.`transaction_id` 
              INNER JOIN transaction_types 
                ON transaction_types.id = transactions.`transaction_type_id` 
            WHERE transaction_types.code = 'return_sale' 
              AND transactions.status = 'active' 
              AND transactions.`branch_id` = branches.`id` {$additionalSpecificFilters}) AS 'returns',
            (SELECT 
              CASE
                WHEN SUM(transactions.discount) IS NULL 
                THEN 0 
                ELSE SUM(transactions.discount) 
              END AS total_discount 
            FROM
              transactions 
              INNER JOIN transaction_types 
                ON transaction_types.id = transactions.`transaction_type_id` 
            WHERE transaction_types.`code` = 'sale' 
              AND transactions.status = 'active' 
              AND transactions.branch_id = branches.id {$additionalSpecificFilters}) AS 'discounts' 
          FROM
            branches
          WHERE branches.id IS NOT NULL
          {$branchKeysSql} ) AS transaction_query 
        ";

        $branchesSalesSummary = DB::select($sql);

        return $branchesSalesSummary;


    }

    public function getSalesSummary($filter)
    {
        $additionalSpecificFilters = $this->getAdditionalTransactionSpecificFilters($filter);

        $sql = "SELECT 
          transaction_query.*,
          (
            transaction_query.sales - transaction_query.returns - transaction_query.discounts
          ) AS 'net_sales',
          (
            transaction_query.sales - transaction_query.cost_of_sales
          ) AS 'gross_profit',
          CASE
            WHEN (
              (
                transaction_query.sales - transaction_query.cost_of_sales
              ) / transaction_query.sales
            ) IS NULL 
            THEN 0 
            ELSE (
              (
                transaction_query.sales - transaction_query.cost_of_sales
              ) / transaction_query.sales
            ) 
          END AS 'gross_margin' FROM (SELECT * FROM ( 
            
            (SELECT 
              CASE
                WHEN SUM(t_item.`quantity` * t_item.`product_franchisee_price`) IS NULL AND transactions.sub_type = 'delivery'
                THEN 0 
                WHEN SUM(t_item.`quantity` * t_item.`product_selling_price`) IS NULL
                THEN 0 
                WHEN transactions.sub_type = 'delivery'
                THEN ROUND(SUM(t_item.`quantity` * t_item.`product_franchisee_price`),2)
                ELSE ROUND(SUM(t_item.`quantity` * t_item.`product_selling_price`),2)
              END AS 'sales'
            FROM
              transaction_items t_item 
              INNER JOIN transactions 
                ON transactions.id = t_item.`transaction_id` 
              INNER JOIN transaction_types 
                ON transaction_types.id = transactions.`transaction_type_id` 
            WHERE transaction_types.code = 'sale' 
              AND transactions.status = 'active' 
              -- AND transactions.`branch_id` = branches.`id` 
              {$additionalSpecificFilters}
              ) AS sales,
              
              
              
            (SELECT 
              CASE
                WHEN SUM(
                  t_item.`quantity` * t_item.`product_cost_price`
                ) IS NULL 
                THEN 0 
                ELSE ROUND(SUM(
                  t_item.`quantity` * t_item.`product_cost_price`
                ),2)
              END AS 'cost_of_sales'
            FROM
              transaction_items t_item 
              INNER JOIN transactions 
                ON transactions.id = t_item.`transaction_id` 
              INNER JOIN transaction_types 
                ON transaction_types.id = transactions.`transaction_type_id` 
            WHERE transaction_types.code = 'sale' 
              AND transactions.status = 'active' 
              -- AND transactions.`branch_id` = branches.`id` 
              {$additionalSpecificFilters}
              ) AS cost_of_sales,
              
              
              
            (SELECT 
              CASE
                WHEN SUM(t_item.`quantity` * t_item.`product_franchisee_price`) IS NULL AND transactions.sub_type = 'delivery'
                THEN 0 
                WHEN SUM(t_item.`quantity` * t_item.`product_selling_price`) IS NULL
                THEN 0 
                WHEN transactions.sub_type = 'delivery'
                THEN ROUND(SUM(t_item.`quantity` * t_item.`product_franchisee_price`),2)
                ELSE ROUND(SUM(t_item.`quantity` * t_item.`product_selling_price`),2)
              END AS 'returns'
            FROM
              transaction_items t_item 
              INNER JOIN transactions 
                ON transactions.id = t_item.`transaction_id` 
              INNER JOIN transaction_types 
                ON transaction_types.id = transactions.`transaction_type_id` 
            WHERE transaction_types.code = 'return_sale' 
              AND transactions.status = 'active' 
              -- AND transactions.`branch_id` = branches.`id` 
              {$additionalSpecificFilters}
              ) AS RETURNS,
              
              
              
            (SELECT 
              CASE
                WHEN SUM(transactions.discount) IS NULL 
                THEN 0 
                ELSE ROUND(SUM(transactions.discount),2)
              END AS 'discounts' 
            FROM
              transactions 
              INNER JOIN transaction_types 
                ON transaction_types.id = transactions.`transaction_type_id` 
            WHERE transaction_types.`code` = 'sale' 
              AND transactions.status = 'active' 
              -- AND transactions.branch_id = branches.id 
              {$additionalSpecificFilters}
              ) AS discounts
            )) AS transaction_query";


        $salesSummary = DB::select($sql)[0];

        return $salesSummary;
    }

    public function getWarehouseLedger($filter)
    {
        $additionalSql = $this->getAdditionalSqlFilters($filter);
        $paginationSql = SqlHelper::getPaginationByFilter($filter);

        $sql = "SELECT 
                  transaction_query.* 
                FROM
                  (SELECT 
                    transactions.*,
                    transaction_types.`name` AS 'transaction_type',
                    transaction_types.`code` AS 'transaction_type_code',
                    transaction_types.`group`,
                    transaction_items.`quantity`,
                    transaction_items.`product_variation_id`,
                    CASE
                      WHEN (transaction_items.remaining_warehouse_quantity > transaction_items.current_warehouse_quantity) AND transaction_types.code IN ('deliver_stock','return_sale','sale','return_stock')
                      THEN '+' 
                      WHEN (transaction_items.remaining_warehouse_quantity < transaction_items.current_warehouse_quantity) AND transaction_types.code IN ('deliver_stock','return_sale','sale','return_stock')
                      THEN '-' 
                      WHEN transaction_items.remaining_quantity > transaction_items.current_quantity 
                      THEN '+' 
                      WHEN transaction_items.remaining_quantity < transaction_items.current_quantity 
                      THEN '-' 
                      ELSE '' 
                    END AS 'operator',
                    CASE
                      WHEN (transaction_items.remaining_warehouse_quantity > transaction_items.current_warehouse_quantity) AND transaction_types.code IN ('deliver_stock','return_sale','sale','return_stock')
                      THEN 'plus' 
                      WHEN (transaction_items.remaining_warehouse_quantity < transaction_items.current_warehouse_quantity) AND transaction_types.code IN ('deliver_stock','return_sale','sale','return_stock')
                      THEN 'minus' 
                      WHEN transaction_items.remaining_quantity > transaction_items.current_quantity 
                      THEN 'plus' 
                      WHEN transaction_items.remaining_quantity < transaction_items.current_quantity 
                      THEN 'minus' 
                      ELSE '' 
                    END AS 'stocks_operator',
                    CASE
                      WHEN transaction_types.code IN ('deliver_stock','return_sale','sale','return_stock')
                      THEN transaction_items.current_warehouse_quantity 
                      ELSE transaction_items.current_quantity
                    END AS 'current_quantity',
                    CASE
                      WHEN transaction_types.code IN ('deliver_stock','return_sale','sale','return_stock')
                      THEN transaction_items.remaining_warehouse_quantity 
                      ELSE transaction_items.remaining_quantity
                    END AS 'remaining_quantity',
                    transaction_items.product_selling_price,
                    transaction_items.product_cost_price,
                    CASE WHEN transaction_types.code = 'sale' THEN ROUND((transaction_items.product_selling_price*transaction_items.quantity),2) ELSE 0 END as 'sales_price',
                    CASE WHEN transaction_types.code = 'sale' THEN ROUND((transaction_items.product_cost_price*transaction_items.quantity),2) ELSE 0 END as 'cost_price',
                    branches.`name` AS 'branch_name',
                    transaction_items.`product_name` 
                  FROM
                    transaction_items 
                    INNER JOIN transactions 
                      ON transactions.`id` = transaction_items.`transaction_id` 
                    INNER JOIN transaction_types 
                      ON transaction_types.`id` = transactions.`transaction_type_id` 
                    LEFT JOIN branches 
                      ON branches.`id` = transactions.`branch_id` 
                  WHERE transaction_items.deleted_at IS NULL) AS transaction_query 
                WHERE transaction_query.id IS NOT NULL 
                  AND (
                    transaction_query.branch_id IS NULL 
                    OR (transaction_query.branch_id IS NOT NULL AND transaction_query.transaction_type_code = 'deliver_stock' AND transaction_query.branch_type IS NULL)
                    OR (transaction_query.branch_id IS NOT NULL AND transaction_query.transaction_type_code = 'return_stock' AND transaction_query.sub_type IS NULL)
                    OR (transaction_query.branch_id IS NOT NULL AND transaction_query.transaction_type_code = 'return_sale' AND transaction_query.sub_type = 'delivery')
                    OR (transaction_query.branch_id IS NOT NULL AND transaction_query.transaction_type_code = 'sale' AND transaction_query.sub_type = 'delivery')
                  ) 
                {$additionalSql}
                {$paginationSql} ";

        $ledger = DB::select($sql);

        return $ledger;

    }

    public function getWarehouseLedgerMeta($filter)
    {
        $limit = SqlHelper::getLimitByFilter($filter);
        $page = SqlHelper::getPageByFilter($filter);

        return [
            'length' => $this->getWarehouseLedgerCountByFilter($filter),
            'limit' => $limit,
            'page' => $page
        ];
    }

    private function getWarehouseLedgerCountByFilter($filter)
    {
        $additionalSql = $this->getAdditionalSqlFilters($filter);

        $sql = "SELECT 
                  COUNT(transaction_query.id) as 'query_count'
                FROM
                  (SELECT 
                    transactions.*,
                    transaction_types.`name` AS 'transaction_type',
                    transaction_types.`code` AS 'transaction_type_code',
                    transaction_types.`group`,
                    transaction_items.`quantity`,
                    transaction_items.`product_variation_id`,
                    CASE
                      WHEN (transaction_items.remaining_warehouse_quantity > transaction_items.current_warehouse_quantity) AND transaction_types.code = 'deliver_stock'
                      THEN '+' 
                      WHEN (transaction_items.remaining_warehouse_quantity < transaction_items.current_warehouse_quantity) AND transaction_types.code = 'deliver_stock'
                      THEN '-' 
                      WHEN transaction_items.remaining_quantity > transaction_items.current_quantity 
                      THEN '+' 
                      WHEN transaction_items.remaining_quantity < transaction_items.current_quantity 
                      THEN '-' 
                      ELSE '' 
                    END AS 'operator',
                    CASE
                      WHEN (transaction_items.remaining_warehouse_quantity > transaction_items.current_warehouse_quantity) AND transaction_types.code = 'deliver_stock' 
                      THEN 'plus' 
                      WHEN (transaction_items.remaining_warehouse_quantity < transaction_items.current_warehouse_quantity) AND transaction_types.code = 'deliver_stock' 
                      THEN 'minus' 
                      WHEN transaction_items.remaining_quantity > transaction_items.current_quantity 
                      THEN 'plus' 
                      WHEN transaction_items.remaining_quantity < transaction_items.current_quantity 
                      THEN 'minus' 
                      ELSE '' 
                    END AS 'stocks_operator',
                    CASE
                      WHEN transaction_types.code = 'deliver_stock' 
                      THEN transaction_items.current_warehouse_quantity 
                      ELSE transaction_items.current_quantity
                    END AS 'current_quantity',
                    CASE
                      WHEN transaction_types.code = 'deliver_stock' 
                      THEN transaction_items.remaining_warehouse_quantity 
                      ELSE transaction_items.remaining_quantity
                    END AS 'remaining_quantity',
                    transaction_items.product_selling_price,
                    transaction_items.product_cost_price,
                    branches.`name` AS 'branch_name',
                    transaction_items.`product_name` 
                  FROM
                    transaction_items 
                    INNER JOIN transactions 
                      ON transactions.`id` = transaction_items.`transaction_id` 
                    INNER JOIN transaction_types 
                      ON transaction_types.`id` = transactions.`transaction_type_id` 
                    LEFT JOIN branches 
                      ON branches.`id` = transactions.`branch_id` 
                  WHERE transaction_items.deleted_at IS NULL) AS transaction_query 
                WHERE transaction_query.id IS NOT NULL 
                  AND (
                    transaction_query.branch_id IS NULL 
                    OR (transaction_query.branch_id IS NOT NULL AND transaction_query.transaction_type_code = 'deliver_stock' AND transaction_query.branch_type IS NULL)
                    OR (transaction_query.branch_id IS NOT NULL AND transaction_query.transaction_type_code = 'return_stock' AND transaction_query.sub_type IS NULL)
                    OR (transaction_query.branch_id IS NOT NULL AND transaction_query.transaction_type_code = 'return_sale' AND transaction_query.sub_type = 'delivery')
                    OR (transaction_query.branch_id IS NOT NULL AND transaction_query.transaction_type_code = 'sale' AND transaction_query.sub_type = 'delivery')
                  ) 
                {$additionalSql} ";

        $ledger = DB::select($sql);

        return $ledger[0]->query_count;
    }

    public function getBranchLedger($filter)
    {
        $additionalSql = $this->getAdditionalSqlFilters($filter);
        $paginationSql = SqlHelper::getPaginationByFilter($filter);

        $sql = "SELECT 
                  transaction_query.* 
                FROM
                  (SELECT 
                    transactions.*,
                    transaction_types.`name` AS 'transaction_type',
                    transaction_types.`group`,
                    transaction_items.current_quantity,
                    transaction_items.`quantity`,
                    transaction_items.`product_variation_id`,
                    CASE
                      WHEN transaction_items.remaining_quantity > transaction_items.current_quantity 
                      THEN '+' 
                      WHEN transaction_items.remaining_quantity < transaction_items.current_quantity 
                      THEN '-' 
                      ELSE '' 
                    END AS 'operator',
                    CASE
                      WHEN transaction_items.remaining_quantity > transaction_items.current_quantity 
                      THEN 'plus' 
                      WHEN transaction_items.remaining_quantity < transaction_items.current_quantity 
                      THEN 'minus' 
                      ELSE '' 
                    END AS 'stocks_operator',
                    transaction_items.remaining_quantity,
                    transaction_items.product_selling_price,
                    transaction_items.product_cost_price,
                    CASE WHEN transaction_types.code = 'sale' THEN ROUND((transaction_items.product_selling_price*transaction_items.quantity),2) ELSE 0 END as 'sales_price',
                    CASE WHEN transaction_types.code = 'sale' THEN ROUND((transaction_items.product_cost_price*transaction_items.quantity),2) ELSE 0 END as 'cost_price',
                    branches.`name` AS 'branch_name',
                    transaction_items.`product_name` 
                  FROM
                    transaction_items 
                    INNER JOIN transactions 
                      ON transactions.`id` = transaction_items.`transaction_id` 
                    INNER JOIN transaction_types 
                      ON transaction_types.`id` = transactions.`transaction_type_id` 
                    LEFT JOIN branches 
                      ON branches.`id` = transactions.`branch_id` 
                  WHERE transaction_items.deleted_at IS NULL 
                  AND (transactions.sub_type IS NULL OR transactions.sub_type NOT IN('delivery'))
                  ) AS transaction_query 
                WHERE transaction_query.id IS NOT NULL 
                {$additionalSql}
                {$paginationSql} ";

        $ledger = DB::select($sql);

        return $ledger;

    }

    public function getBranchLedgerMeta($filter)
    {
        $limit = SqlHelper::getLimitByFilter($filter);
        $page = SqlHelper::getPageByFilter($filter);

        return [
            'length' => $this->getBranchLedgerCountByFilter($filter),
            'limit' => $limit,
            'page' => $page
        ];
    }

    private function getBranchLedgerCountByFilter($filter)
    {
        $additionalSql = $this->getAdditionalSqlFilters($filter);

        $sql = "SELECT 
                  COUNT(transaction_query.id) AS 'query_count' 
                FROM
                  (SELECT 
                    transactions.*,
                    transaction_types.`name` AS 'transaction_type',
                    transaction_types.`group`,
                    transaction_items.current_quantity,
                    transaction_items.`quantity`,
                    transaction_items.`product_variation_id`,
                    CASE
                      WHEN transaction_items.remaining_quantity > transaction_items.current_quantity 
                      THEN '+' 
                      WHEN transaction_items.remaining_quantity < transaction_items.current_quantity 
                      THEN '-' 
                      ELSE '' 
                    END AS 'operator',
                    transaction_items.remaining_quantity,
                    transaction_items.product_selling_price,
                    transaction_items.product_cost_price,
                    branches.`name` AS 'branch_name',
                    transaction_items.`product_name` 
                  FROM
                    transaction_items 
                    INNER JOIN transactions 
                      ON transactions.`id` = transaction_items.`transaction_id` 
                    INNER JOIN transaction_types 
                      ON transaction_types.`id` = transactions.`transaction_type_id` 
                    LEFT JOIN branches 
                      ON branches.`id` = transactions.`branch_id` 
                  WHERE transaction_items.deleted_at IS NULL
                  AND (transactions.sub_type IS NULL OR transactions.sub_type NOT IN('delivery'))
                  ) AS transaction_query 
                WHERE transaction_query.id IS NOT NULL 
                {$additionalSql} ";

        $ledger = DB::select($sql);

        return $ledger[0]->query_count;
    }

    public function getAllOrNumbersByBranchId($id) {

        $activeFlag = StatusHelper::ACTIVE;

        $orNumbers = Transaction::select('or_no', 'created_at')->where('status', $activeFlag)
            ->where('branch_id', $id)->get()->toArray();

        return $orNumbers;
    }

}