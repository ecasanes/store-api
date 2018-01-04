<?php namespace App\Mercury\Services;

use App\Mercury\Repositories\ExportInterface;
use App\Mercury\Repositories\UserInterface;
use App\Mercury\Repositories\TransactionInterface;
use App\Mercury\Repositories\ProductInterface;
use App\Mercury\Repositories\PriceRuleInterface;
use App\Mercury\Repositories\ProductCategoryInterface;
use App\Mercury\Repositories\BranchInterface;
use App\Mercury\Repositories\CompanyStockInterface;
use App\Mercury\Repositories\BranchStockInterface;

use App\PriceRule;
use App\TransactionType;
use Carbon\Carbon;

class ExportService
{
    protected $export;
    protected $user;
    protected $transaction;
    protected $product;
    protected $pricing;
    protected $category;
    protected $branch;
    protected $companyStock;
    protected $branchStock;

    public function __construct(
        ExportInterface $export,
        UserInterface $user,
        TransactionInterface $transaction,
        ProductInterface $product,
        PriceRuleInterface $pricing,
        ProductCategoryInterface $category,
        BranchInterface $branch,
        CompanyStockInterface $companyStock,
        BranchStockInterface $branchStock
    )
    {
        $this->export = $export;
        $this->user = $user;
        $this->transaction = $transaction;
        $this->product = $product;
        $this->pricing = $pricing;
        $this->category = $category;
        $this->branch = $branch;
        $this->companyStock = $companyStock;
        $this->branchStock = $branchStock;
    }

    public function export($data)
    {
        $code = $data['code'];

        if($code == 'reports') {
            $reports = $this->exportReports($data);
            return $reports;
        }

        $exportData = $data['export'];

        if($code != 'deliveries') {
            $filters = $this->getFiltersArray($data);
            $filtersAreSet = $this->checkForFilters($filters);
        } else {
            $filtersAreSet = false;
        }

        # if user HAS NOT selected filter options from the export modal
        if(!$filtersAreSet && $code == 'stocks') {
            $companyStocksData = $this->getFilteredData($filters, 'company_stocks');
            $branchStocksData = $this->getFilteredData($filters, 'branch_stocks');
            $exportData['company_stocks'] = $companyStocksData;
            $exportData['branch_stocks'] = $branchStocksData;
        }

        # if user HAS SET filter options from the export modal
        if($filtersAreSet) {

            if($code != 'stocks') {
                $exportData = $this->getFilteredData($filters, $code);
            }

            if($code == 'stocks') {
                $companyStocksData = null;
                $branchStocksData = $this->getFilteredData($filters, 'branch_stocks');
                # if export stocks data for all
                if($filters['branch_id'] == 0) {
                    $companyStocksData = $this->getFilteredData($filters, 'company_stocks');
                    $branchStocksData = $this->getFilteredData($filters, 'branch_stocks');
                }

                $exportData['company_stocks'] = $companyStocksData;
                $exportData['branch_stocks'] = $branchStocksData;
            }
        }

        if(!$exportData) {
            return false;
        }

        if($code != 'stocks') {
            $exportData = $this->unsetData($exportData, $code);

            if($code == 'deliveries') {
                $data['export'] = $exportData;
                $exportData = $this->setDeliveriesData($data);
            }
        }

        $columnHeadings = $this->getColumnHeadings($code);

        if($code == 'stocks') {
            $branches = $this->getAllbranches();
        }

        # call to separate the sale items
        if($code == 'sales') {
            $exportData = $this->separateSaleItems($exportData);
        }

        if($code != 'stocks') {
            $export = $this->export->export($exportData, $code, $columnHeadings);
        } else {
            $export = $this->export->exportStocksOnHand($exportData, $branches, $columnHeadings, $code, $filters);
        }

        return $export;
    }

    private function getFiltersArray($data)
    {
        unset($data['export']);
        unset($data['code']);
        unset($data['token']);

        # initialize filters array
        foreach($data as $key => $filter) {
            $filters[$key] = $filter;
        }

        return $filters;
    }

    private function checkForFilters($filters)
    {
        # check which filters are set
        foreach($filters as $key => $filter) {
            if($filter && isset($filter) || $filter === 0) {
                $setFilters[$key] = $filter;
            }
        }

        if(!isset($setFilters)) {
            return false;
        }

        return true;
    }

    private function getFilteredData($filters, $code)
    {

        switch($code) {
            case 'staff':
            case 'customers':
                $data = $this->user->filter($filters);
                break;

            case 'adjustments':
                break;
            case 'sales':
                $data = $this->transaction->filter($filters);
                break;

            case 'products':
                $data = $this->product->filter($filters);
                break;

            case 'pricing':
                $data = $this->pricing->filter($filters);
                break;

            case 'categories':
                $data = $this->category->filter($filters);
                break;

            case 'company_stocks':
                $data = [];
                # store stocks from warehouse/company
                $companyStocks = $this->companyStock->filter($filters);
                $companyStocks = $this->unsetData($companyStocks, 'company_stocks');
                $data[] = $companyStocks;

                break;

            case 'branch_stocks':

                # if branch specific stocks
                if($filters['branch_id'] && $filters['branch_id'] != 0) {

                    $branchId = $filters['branch_id'];
                    $branch = $this->branch->find($branchId)->toArray();
                    $branchStocksData = $this->branchStock->getBranchStocksById($branchId, $filters);

                    foreach($branchStocksData as $stock) {
                        $branchStocksArray[] = (array) $stock;
                    }

                    $branchStocksArray = $this->unsetData($branchStocksArray, 'branch_stocks');

                    $branchStocks = [];
                    $branchStocks['branch_id'] = $branch['id'];
                    $branchStocks['branch_name'] = $branch['name'];
                    $branchStocks['branch_stocks'] = $branchStocksArray;

                }


                # if all branch stocks
                if(!$filters['branch_id'] || $filters['branch_id'] == 0) {
                    $branchStocks = [];
                    # store stocks from all branches
                    $branches = $this->branch->getAll();

                    foreach($branches as $key => $branch) {
                        if(strtolower($branch->name) == strtolower('DUMMY')) {
                            unset($branches[$key]);
                        }
                    }

                    foreach($branches as $key => $branch) {
                        $branchStocks[$key]['branch_id'] = $branch->id;
                        $branchStocks[$key]['branch_name'] = $branch->name;
                        $branchStocks[$key]['branch_stocks'] = $this->branchStock->getBranchStocksById($branch->id, $filters);
                    }

                    foreach($branchStocks as $key => $branches) {
                        $branchStocksArray = [];
                        foreach($branches['branch_stocks'] as $stock) {
                            $branchStocksArray[] = (array) $stock;
                        }
                        $unsetBranchStocksArrayData = $this->unsetData($branchStocksArray, $code);
                        $branchStocks[$key]['branch_stocks'] = $unsetBranchStocksArrayData;
                    }
                }

                $data = $branchStocks;
                break;
        }

        return $data;
    }

    private function getColumnHeadings($code)
    {
        switch ($code) {

            case 'customers':
                $columnHeadings = [
                    'Firstname',
                    'Lastname',
                    'Customer ID',
                    'Branch',
                    'Email',
                    'Phone',
                    'City',
                    'Province',
                    'Zip'
                ];
                break;

            case 'adjustments':
            case 'sales':
                $columnHeadings = [
                    'OR Number',
                    'Transaction Name',
                    'Customer Firstname',
                    'Customer Lastname',
                    'Items',
                    'Total Price',
                    'Branch',
                    'Staff Firstname',
                    'Staff Lastname',
                    'Date',
                ];
                break;

            case 'products':
                $columnHeadings = [
                    'Name',
                    'Category',
                    'Cost Price',
                    'Selling Price',
                    'Quantity',
                    'Total'
                ];
                break;

            case 'pricing':
                $columnHeadings = [
                    'RULE NAME',
                    'APPLIES TO PRODUCT',
                    'RULE TYPE',
                    'DISCOUNT TYPE',
                    'DISCOUNT',
                    'AMOUNT / QUANTITY',
                    'APPLIES TO'
                ];
                break;

            case 'categories':
                $columnHeadings = [
                    'Name',
                    'Code'
                ];
                break;

            case 'staff':
                $columnHeadings = [
                    'Firstname',
                    'Lastname',
                    'Branch',
                    'Sold Items',
                ];
                break;

            case 'stocks':
                $columnHeadings = [
                    'Product Name',
                    'Code',
                    'Current Inventory',
                    'Total Sold Items'
                ];
                break;

            case 'deliveries':
                $columnHeadings = [
                    'Items',
                    'Quantity'
                ];
                break;

        }
        return $columnHeadings;
    }

    // TODO refactor this to unset only unspecified keys
    private function unsetData($data, $code)
    {
        foreach($data as $key => $export) {

            if(gettype($data[$key] == 'object')) {
                $export = (array) $export;
            }

            switch ($code) {

                case 'staff':
                case 'customers':
                    unset($export['id']);
                    unset($export['branch_id']);
                    unset($export['branch_id_registered']);
                    unset($export['branch_registered']);

                    if($code == 'customers') {
                        unset($export['can_void']);
                        unset($export['staff_id']);
                    }

                    if($code == 'staff') {
                        unset($export['city']);
                        unset($export['province']);
                        unset($export['zip']);
                        unset($export['customer_id']);
                        unset($export['email']);
                        unset($export['staff_id']);
                        unset($export['phone']);
                        unset($export['has_multiple_access']);

                        if($export['can_void'] == 1) {
                            $export['can_void'] = 'Yes';
                        } else {
                            $export['can_void'] = 'No';
                        }

                        unset($export['can_void']);

                        $export['sold_items'] = (int)$export['sold_items'];
                    }

                    unset($export['permission_names']);
                    unset($export['role_code']);
                    unset($export['role_name']);
                    unset($export['status']);
                    unset($export['address']);
                    break;

                case 'adjustments':
                case 'sales':
                    unset($export['id']);
                    unset($export['branch_id']);
                    unset($export['customer_phone']);
                    unset($export['customer_user_id']);
                    unset($export['deleted_at']);
                    unset($export['discount']);
                    unset($export['discount_type']);
                    unset($export['grand_total']);
                    unset($export['group']);
                    unset($export['price_rule_code']);
                    unset($export['price_rule_id']);
                    unset($export['query_string']);
                    unset($export['referenced_transaction_id']);
                    unset($export['remarks']);
                    unset($export['shortover_string']);
                    unset($export['staff_id']);
                    unset($export['staff_phone']);
                    unset($export['status']);
                    unset($export['transaction_type_description']);
                    unset($export['transaction_type_id']);
                    unset($export['transaction_type']);
                    unset($export['updated_at']);
                    unset($export['user_id']);
                    unset($export['customer_id']);
                    unset($export['franchisee_total']);
                    break;

                case 'products':
                    unset($export['branch_id']);
                    unset($export['branch_quantity']);
                    unset($export['category_code']);
                    unset($export['code']);
                    unset($export['id']);
                    unset($export['image_url']);
                    unset($export['product_category_id']);
                    unset($export['product_id']);
                    unset($export['product_variation_id']);
                    unset($export['total_branch_quantity']);

                    $export['name'] = $export['name'].' ('.$export['size'].' '.$export['metrics'].')';
                    unset($export['size']);
                    unset($export['metrics']);

                case 'pricing':

                    $defaultName = 'All Products';

                    if(!$export['amount'] && $export['quantity']) {
                        unset($export['amount']);
                        $export['quantity'] = $export['quantity'].' pcs.';
                    }

                    if(!$export['quantity'] && $export['amount']) {
                        $export['quantity'] = '₱ '.$export['amount'];
                        unset($export['amount']);
                    }

                    if(!$export['quantity'] && !$export['amount']) {
                        unset($export['quantity']);
                        $export['amount'] = 'N/A';
                    }

                    if($export['discount_type'] == 'percent') {
                        $export['discount'] = $export['discount'].'%';
                    }

                    if($export['discount_type'] == 'fixed') {
                        $export['discount'] = '₱ '.$export['discount'];
                    }

                    if($export['product_name'] && $export['size'] && $export['metrics']) {
                        $export['product_name'] = $export['product_name'].' ('.$export['size'].' '.$export['metrics'].')';
                    }

                    if(!$export['product_variation_id'] && !$export['product_id']) {
                        $export['product_name'] = $defaultName;
                    }

                    unset($export['status']);
                    unset($export['id']);
                    unset($export['code']);
                    unset($export['description']);
                    unset($export['product_variation_id']);
                    unset($export['product_id']);
                    unset($export['size']);
                    unset($export['metrics']);

                case 'categories':
                    unset($export['id']);
                    unset($export['color']);

                case 'general_summary':
                    break;

                case 'sales_summary':
                    break;

                case 'top_items':
                    break;

                case 'company_stocks':
                    unset($export['product_variation_id']);
                    unset($export['company_quantity']);
                    unset($export['selling_price']);
                    unset($export['id']);
                    unset($export['branch_quantity']);
                    unset($export['size']);
                    unset($export['metrics']);
                    unset($export['cost_price']);
                    unset($export['name']);
                    unset($export['image_url']);
                    unset($export['return_sale_item_count']);
                    unset($export['sale_item_count']);
                    break;

                case 'branch_stocks':
                    unset($export['id']);
                    unset($export['branch_id']);
                    unset($export['name']);
                    unset($export['image_url']);
                    unset($export['product_category_id']);
                    unset($export['category']);
                    unset($export['category_code']);
                    unset($export['cost_price']);
                    unset($export['selling_price']);
                    unset($export['metrics']);
                    unset($export['size']);
                    unset($export['status']);
                    unset($export['product']);
                    unset($export['product_id']);
                    unset($export['company_quantity']);
                    unset($export['current_delivery_quantity']);
                    unset($export['sale_item_count']);
                    unset($export['return_sale_item_count']);
                    unset($export['last_delivery_quantity_temp']);
                    unset($export['branch_total_delivery_quantity']);
                    unset($export['last_delivery_quantity_old']);
                    unset($export['last_delivery_quantity']);
                    unset($export['branch_delivery_percentage']);
                    break;

                case 'deliveries':
                    $export['name'] = $export['name'].' '.'('.$export['size'].' '.$export['metrics'].')';
                    $export['quantity'] = (int) $export['quantity'];
                    unset($export['created_at']);
                    unset($export['deleted_at']);
                    unset($export['delivery_id']);
                    unset($export['id']);
                    unset($export['metrics']);
                    unset($export['product_variation_id']);
                    unset($export['size']);
                    unset($export['status']);
                    unset($export['updated_at']);
                    break;
            }

            $setData[] = $export;

        }
        return $setData;
    }

    private function exportReports($data)
    {
        $code = $data['code'];

        $reportType = $data['reportType'];

        $branchId = $data['branchId'];

        $branches = $data['branches'];

        $from = $data['from'];

        $to = $data['to'];

        # if export reports for all branches
        if(!$branchId) {
           switch ($reportType) {
               case 'general_summary':
//                   dd("general all");
                   $allBranches = $data['export'];
                   $allBranches['branch_name'] = 'All Branches';
                   $exportData = $this->getGeneralSummary($branches, $from, $to);
                   array_unshift($exportData, $allBranches);
                   $export = $this->export->exportGeneralSummary($exportData, $branches, $from, $to);
                   break;

               case 'sales_summary':
//                   dd("sales all");
                   $allBranches = $data['export'];
                   $allBranches['branch_name'] = 'All Branches';
                   $exportData = $this->getSalesSummary($branches, $from, $to);
                   array_unshift($exportData, $allBranches);
                   $export = $this->export->exportSalesSummary($exportData, $branches, $from, $to);
                   break;

               case 'top_items':
//                   dd("top items all");
                   $exportData = $this->getTopItems($branches, $from, $to);
                   $export = $this->export->exportTopItems($exportData, $branches, $from, $to);
                   break;
           }
        }

        # if export reports for only one branch
        if($branchId) {
            switch($reportType) {
                case 'general_summary':
//                    dd("general single");
                    $branches = [['id' => $branchId]];
                    $exportData = $this->getGeneralSummary($branches, $from, $to);
                    $export = $this->export->exportGeneralSummary($exportData, $branches, $from, $to);
                    break;
                case 'sales_summary':
//                    dd("sales single");
                    $branches = [['id' => $branchId]];
                    $exportData = $this->getSalesSummary($branches, $from, $to);
                    $export = $this->export->exportSalesSummary($exportData, $branches, $from, $to);
                    break;
                case 'top_items':
//                    dd("top items single");
                    $branches = [['id' => $branchId]];
                    $exportData = $this->getTopItems($branches, $from, $to);
                    $export = $this->export->exportTopItems($exportData, $branches, $from, $to);
                    break;
            }
        }

        return $export;
    }

    private function getGeneralSummary($branches, $from, $to)
    {
        foreach($branches as $key => $branch) {
            $filters = [
                'branch_id' => $branch['id'],
                'from' => $from,
                'to' => $to
            ];

            $branchProducts = $this->transaction->getAllProductsSalesSummary($filters);

            $allProducts[] = $branchProducts;

            # if all branches
            if(isset($branch['name'])) {
                $allProducts[$key]['branch_name'] = $branch['name'];
            }
            # if only one branch
            else {
                $branch = $this->branch->find($branch['id']);
                $allProducts[$key]['branch_name'] = $branch->name;
            }

        }
        return $allProducts;
    }

    private function getSalesSummary($branches, $from, $to)
    {
        foreach($branches as $key => $branch) {

            $filters = [
                'branch_id' => $branch['id'],
                'from' => $from,
                'to' => $to
            ];

            $revenue = $this->transaction->getTotalSales($filters);
            $costOfSales = $this->transaction->getTotalCostOfSales($filters);
            $returnSales = $this->transaction->getTotalReturnSales($filters);
            $discounts = $this->transaction->getTotalDiscounts($filters);
            $netSales = $this->calculateNetSales($revenue, $returnSales, $discounts);
            $grossProfit = $this->calculateGrossProfit($revenue, $costOfSales);
            $grossProfitMargin = $this->calculateGrossProfitMargin($grossProfit, $revenue);

            $salesSummary = [
                'sales' => $revenue,
                'returns' => $returnSales,
                'cost_of_sales' => $costOfSales,
                'discounts' => $discounts,
                'net_sales' => $netSales,
                'gross_profit' => $grossProfit,
                'gross_margin' => $grossProfitMargin
            ];

            $salesSummaries[] = $salesSummary;

            # if all branches
            if(isset($branch['name'])) {
                $salesSummaries[$key]['branch_name'] = $branch['name'];
            }
            # if only one branch
            else {
                $branch = $this->branch->find($branch['id']);
                $salesSummaries[$key]['branch_name'] = $branch->name;
            }
        }

        return $salesSummaries;
    }

    private function getTopItems($branches, $from, $to)
    {
        if(count($branches) != 1) {
            $allBranches = [
                'id' => -1,
                'name' => 'All Branches'
            ];

            array_unshift($branches, $allBranches);
        }


        $allBranchTopItems = [];

        foreach($branches as $key => $branch) {
            $filters = [
                'branch_id' => $branch['id'],
                'from' => $from,
                'to' => $to
            ];

            if($branch['id'] == -1) {
                unset($filters['branch_id']);
            }

            $topBranchItems = $this->transaction->getTopItems($filters);

            $allBranchTopItems[] = $topBranchItems;

            # if all branches
            if(isset($branch['name'])) {
                $allBranchTopItems[$key]['branch_name'] = $branch['name'];
            }
            # if only one branch
            else {
                $branch = $this->branch->find($branch['id']);
                $allBranchTopItems[$key]['branch_name'] = $branch->name;
            }

        }

        return $allBranchTopItems;
    }

    private function calculateNetSales($revenue, $returnSales, $discounts)
    {

        return $revenue - $returnSales - $discounts;

    }

    private function calculateGrossProfit($revenue, $costOfSales)
    {

        return $revenue - $costOfSales;

    }

    private function calculateGrossProfitMargin($grossProfit, $revenue)
    {
        if($revenue<=0){
            return 0;
        }

        return $grossProfit / $revenue;

    }

    private function getAllbranches()
    {
        $allBranches = [];
        $branches = $this->branch->getAll();

        # get all branches
        foreach($branches as $key => $branch) {
            if(strtolower($branch->name) != 'dummy') {
                $allBranches[$key]['id'] = $branch->id;
                $allBranches[$key]['name'] = $branch->name;
            }
        }

        return $allBranches;
    }

    private function setDeliveriesData($data)
    {
        $data['date'] = Carbon::parse($data['date'])->format('MdY_hiA');
        $totalDeliveriesIndex = count($data['export']);

        $data['export'][$totalDeliveriesIndex] = [
            'name' => 'TOTAL ITEMS',
            'quantity' => $data['total']
        ];

        $data['status'] = ucfirst($data['status']);

        if($data['status'] == 'return') {
            $data['status'] = 'Returned';
        }

        unset($data['total']);

        return $data;

    }

    private function separateSaleItems($data){

        $multipleItemsIndex = [];
        $newArray = [];

        # loop to retrive the arrays with multiple sale items stored in $multipleItemsIndex[]
        foreach($data as $key => $sales) {

            # set sale items from items_string
            $saleItems = $sales['items_string'];
            $items = explode(',', $saleItems);
            $itemsCount = count($items);

            if($itemsCount > 1) {
                $multipleItemsIndex[] = $key;
            }


        }

        # loop to store the separated sale arrays into $newArray[] and unset from the original array $data[]
        foreach($multipleItemsIndex as $index) {
            $itemsArray = $data[$index];

            $items = explode(',', $itemsArray['items_string']);

            foreach($items as $item) {
                $itemsArray['items_string'] = $item;
                $newArray[] = $itemsArray;
            }

            unset($data[$index]);
        }


        foreach($newArray as $new) {
            array_unshift($data, $new);
        }

        rsort($data);

        return $data;
    }
}
