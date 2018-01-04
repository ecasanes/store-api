<?php

namespace App\Mercury\Api\Controllers;

use App\Mercury\Helpers\Rest;
use App\Mercury\Helpers\StatusHelper;
use App\Mercury\Services\ActivityService;
use App\Mercury\Services\CompanyService;
use App\Mercury\Services\ProductService;
use App\Mercury\Services\PusherService;
use App\Mercury\Services\RoleService;
use App\Mercury\Services\TransactionService;
use App\Mercury\Services\UserService;
use App\Mercury\Services\ExportService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class TransactionController extends Controller
{
    protected $productService;
    protected $transactionService;
    protected $companyService;
    protected $userService;
    protected $roleService;
    protected $activityService;
    protected $exportService;
    protected $pusherService;

    public function __construct(
        Request $request,
        ProductService $productService,
        TransactionService $transactionService,
        CompanyService $companyService,
        UserService $userService,
        RoleService $roleService,
        ActivityService $activityService,
        ExportService $exportService,
        PusherService $pusherService
    )
    {
        parent::__construct($request);

        $this->productService = $productService;
        $this->transactionService = $transactionService;
        $this->companyService = $companyService;
        $this->userService = $userService;
        $this->roleService = $roleService;
        $this->activityService = $activityService;
        $this->exportService = $exportService;
        $this->pusherService = $pusherService;
    }

    public function find($transactionId)
    {
        // key checks are done in the middleware so don't worry
        $transaction = $this->transactionService->find($transactionId);

        if (!$transaction) {
            return Rest::notFound("Transaction not found");
        }

        return Rest::success($transaction);
    }

    public function getAll()
    {
        $payload = $this->payload;
        $data = $payload->all();

        $productsMeta = $this->transactionService->getFilterMeta($data);
        $products = $this->transactionService->filter($data);

        return Rest::success($products, $productsMeta);
    }

    public function getAllTransactionTypes()
    {
        $data = $this->payload->all();

        $transactionTypes = $this->transactionService->transactionTypesFilter($data);

        return Rest::success($transactionTypes);
    }

    public function findByOr($orNo)
    {
        $saleCode = StatusHelper::SALE;

        $payload = $this->payload;
        $data = $payload->all();

        $branchKey = null;
        $branchId = null;
        $branch = null;
        $includeVoid = false;

        if (isset($data['include_void'])) {
            $includeVoid = $data['include_void'];
        }

        if(isset($data['key'])){
            $branchKey = $data['key'];
        }

        if($branchKey){
            $branch = $this->companyService->findBranchByKey($branchKey);
        }

        if($branch){
            $branchId = $branch->id;
        }

        if(isset($data['branch_id'])){
            $branchId = $data['branch_id'];
        }

        $transaction = $this->transactionService->findByOr($orNo, $saleCode, $includeVoid, $branchId);

        if (!$transaction) {
            return Rest::notFound("Transaction not found");
        }

        return Rest::success($transaction);

    }

    public function getBranchesSalesSummary()
    {
        $payload = $this->payload;
        $filter = $payload->all();

        $totalSales = $this->transactionService->getBranchesSalesSummary($filter);

        return Rest::success($totalSales);
    }

    public function getAllOrNumbersByBranchId($id) {

        $orNumbers = $this->transactionService->getAllOrNumbersByBranchId($id);

        return Rest::success($orNumbers);

    }


    /* Admin */

    public function voidTransactionsById($transactionId)
    {
        $staffId = $this->staffId; // from middleware
        $roles = $this->roles; // from middleware

        $voidFlag = StatusHelper::VOID;

        $saleCode = StatusHelper::SALE;
        $returnSaleCode = StatusHelper::RETURN_SALE;

        $shortAdjustmentCode = StatusHelper::ADJUSTMENT_SHORT;
        $shortOverAdjustmentCode = StatusHelper::ADJUSTMENT_SHORTOVER;

        $transaction = $this->transactionService->find($transactionId);

        if (!$transaction) {
            return Rest::notFound("Transaction not found.");
        }

        $transactionType = $transaction->transaction_type;

        $transactionStatus = $transaction->status;
        if ($transactionStatus == $voidFlag) {
            return Rest::failed("Transaction already voided.");
        }

        if (
            $transactionType !== $saleCode &&
            $transactionType !== $returnSaleCode &&
            $transactionType !== $shortAdjustmentCode &&
            $transactionType !== $shortOverAdjustmentCode
        ) {

            return Rest::failed("Cannot void this transaction");

        }

        $hasEnoughPermissions = $this->roleService->hasEnoughPermissionsByRole(StatusHelper::COMPANY_STAFF, $roles);

        if (!$staffId && !$hasEnoughPermissions) {
            return Rest::failed("Cannot void this transaction. You don't have enough permission.");
        }

        if ($transactionType == $saleCode) {
            return $this->voidSaleTransaction($transactionId);
        }

        if ($transactionType == $returnSaleCode) {
            return $this->voidReturnSaleTransaction($transactionId);
        }

        if ($transactionType == $shortAdjustmentCode) {
            return $this->voidAdjustmentShortTransaction($transactionId);
        }

        if ($transactionType == $shortOverAdjustmentCode) {
            return $this->voidAdjustmentShortOverTransaction($transactionId);
        }
    }

    public function getTotalSales()
    {
        $payload = $this->payload;
        $data = $payload->all();

        $totalSales = $this->transactionService->getTotalSales($data);

        return Rest::success($totalSales);
    }

    public function getTopSaleItems()
    {
        $payload = $this->payload;
        $data = $payload->all();

        $topItems = $this->transactionService->getTopSaleItems($data);

        return Rest::success($topItems);
    }

    public function getSalesSummary()
    {
        $payload = $this->payload;
        $filter = $payload->all();

        $salesSummary = $this->transactionService->getSalesSummary($filter);

        return Rest::success($salesSummary);
    }

    public function getSalesChanges()
    {
        $payload = $this->payload;
        $filter = $payload->all();

        $salesChanges = $this->transactionService->getSalesChanges($filter);

        return Rest::success($salesChanges);
    }

    public function getAllProductsSalesSummary()
    {
        $payload = $this->payload;
        $data = $payload->all();

        $excludeEmpty = false;

        if(isset($data['exclude_empty'])){
            $excludeEmpty = $data['exclude_empty'];
        }

        if($excludeEmpty){
            $salesSummary = $this->transactionService->getProductsSalesSummary($data);
            return Rest::success($salesSummary);
        }

        $salesSummary = $this->transactionService->getAllProductsSalesSummary($data);

        return Rest::success($salesSummary);
    }

    public function getDailySales()
    {
        $payload = $this->payload;
        $data = $payload->all();

        $salesSummary = $this->transactionService->getDailySales($data);

        return Rest::success($salesSummary);
    }

    public function createSaleTransaction()
    {
        $payload = $this->payload;
        $data = $payload->all();

        $validator = $this->validator($data, [
            'or_no' => 'required',
            'items' => 'required',
            'branch_id' => 'required',
            'staff_id' => 'required'
        ]);

        if ($validator->fails()) {
            return Rest::validationFailed($validator);
        }

        /**
         * SET THE TRANSACTION DATE
         */
        $transactionDate = Carbon::now()->toDateTimeString();
        if(isset($data['created_at'])) {
            $createdAt = $data['created_at'];

            if(isset($data['created_at']['year'])) {
                $year = $createdAt['year'];
                $month = $createdAt['month'];
                $day = $createdAt['day'];
                $transactionDate = $year.'-'.$month.'-'.$day;
                $transactionDate = Carbon::parse($transactionDate)->toDateTimeString();
            }
        }

        $branchId = $payload->branch_id;

        $branch = $this->companyService->findBranch($branchId);

        if (!$branch) {
            return Rest::notFound("Cannot add transaction. Please select valid branch.");
        }

        $staffId = $payload->staff_id;

        $staff = $this->companyService->findUserByStaffId($staffId);

        if(!$staff){
            return Rest::notFound("Cannot add transaction. Please select valid staff.");
        }

        if (empty($data['items'])) {
            return Rest::failed("Cannot add transaction. Please input at least 1 Product.");
        }

        $items = $payload->items;
        $orNumber = $payload->or_no;

        $saleTransactionCode = StatusHelper::SALE;
        $includeVoid = false;

        $existingSaleTransaction = $this->transactionService->findByOr($orNumber, $saleTransactionCode, $includeVoid, $branchId);

        if ($existingSaleTransaction) {
            return Rest::failed("Cannot add transaction. Sale with the same OR NUMBER already exists.");
        }

        $itemValidations = $this->productService->validateEnoughBranchStocks($branchId, $items);
        $valid = $this->productService->hasEnoughStocksByValidation($itemValidations);

        if (!$valid) {
            // FIXME: detailed message for which product variation id has not enough stocks
            return Rest::failed("Cannot add transaction. Not enough stocks.");
        }

        $priceRuleId = null;
        $customerUser = null;
        $customerId = null;

        $discountRemarks = null;

        if (isset($data['customer_id'])) {
            $customerId = $data['customer_id'];
            $customerUser = $this->userService->findByCustomerId($customerId);
        }

        if(isset($data['customer_user_id'])) {
            $customerUserId = $data['customer_user_id'];
            $customerUser = $this->userService->find($customerUserId);
        }

        if (isset($data['price_rule_id'])){
            $priceRuleId = $data['price_rule_id'];
        }

        if (isset($data['discount_remarks'])){
            $discountRemarks = $data['discount_remarks'];
        }

        $this->productService->subtractBranchStocks($branchId, $items);
        $this->productService->addSoldItemsCount($branchId, $items);
        $transactionDetails = $this->transactionService->createSaleTransaction($branchId, $staffId, $orNumber, $items, $priceRuleId);

        if (!isset($transactionDetails['transaction'])) {
            return Rest::failed("Something went wrong while creating transaction");
        }

        $transaction = $transactionDetails['transaction'];
        $transactionItems = $transactionDetails['items'];

        $transactionId = $transaction->id;

        if ($customerUser) {
            $discount = $this->transactionService->calculateDiscountByItems($transactionItems, StatusHelper::MEMBER, $priceRuleId);
            $this->transactionService->update($transactionId, [
                'customer_user_id' => $customerUser->id,
                'customer_id' => $customerId,
                'customer_firstname' => $customerUser->firstname,
                'customer_lastname' => $customerUser->lastname,
                'customer_phone' => $customerUser->phone,
                'discount' => $discount,
                'discount_remarks' => $discountRemarks,
                'created_at' => $transactionDate
            ]);
            $this->transactionService->updateTransactionItemsDiscount($transactionId, StatusHelper::MEMBER, $priceRuleId);
        }

        if (!$customerUser) {
            $discount = $this->transactionService->calculateDiscountByItems($transactionItems, StatusHelper::GUEST, $priceRuleId);
            $this->transactionService->update($transactionId, [
                'customer_firstname' => $data['customer_firstname'],
                'customer_lastname' => $data['customer_lastname'],
                'discount' => $discount,
                'discount_remarks' => $discountRemarks,
                'created_at' => $transactionDate
            ]);
            $this->transactionService->updateTransactionItemsDiscount($transactionId, StatusHelper::GUEST, $priceRuleId);
        }

        $this->activityService->logAddSale($staffId, $transactionId);
        $this->pusherService->triggerRefreshSaleEvent();
        $this->getProductAlertsByTransactionId($transactionId, $staffId);

        return Rest::success($transactionDetails, [
            'payload' => $data
        ]);

    }



    /* POS GET */

    public function getByBranchKey()
    {
        // key checks are done in the middleware so don't worry

        $payload = $this->payload;
        $filter = $payload->all();
        $key = $payload->key;

        $branch = $this->companyService->findBranchByKey($key);
        $branchId = $branch->id;

        $filter['branch_id'] = $branchId;

        $productsMeta = $this->transactionService->getFilterMeta($filter);
        $products = $this->transactionService->filter($filter);

        return Rest::success($products, $productsMeta);
    }

    public function getProductCategoriesByTransactionId($transactionId)
    {
        $categories = $this->productService->getProductCategoriesByTransactionId($transactionId);

        return Rest::success($categories);
    }

    public function getProductsByTransactionId($transactionId)
    {
        $payload = $this->payload;
        $data = $payload->all();

        $products = $this->productService->getByTransactionId($transactionId, $data);
        $meta = $this->productService->getByTransactionIdMeta($transactionId, $data);

        return Rest::success($products, $meta);
    }


    /* POS SALE TRANSACTION */

    public function createSaleTransactionByBranchKey()
    {
        $payload = $this->payload;
        $data = $payload->all();

        // payload key and staff id are always needed for this controller
        // with the validations already found in middleware
        $key = $payload->key;
        $staffId = $payload->staff_id;

        $branch = $this->companyService->findBranchByKey($key);

        if (!$branch) {
            return Rest::notFound("Branch not found");
        }

        $branchId = $branch->id;

        $validator = $this->validator($data, [
            'or_no' => 'required',
            'items' => 'required'
        ]);

        if ($validator->fails()) {
            return Rest::validationFailed($validator);
        }

        if (empty($data['items'])) {
            return Rest::failed("Please input at least 1 Product.");
        }

        $items = $payload->items;
        $orNumber = $payload->or_no;

        $saleTransactionCode = StatusHelper::SALE;
        $includeVoid = false;

        $existingSaleTransaction = $this->transactionService->findByOr($orNumber, $saleTransactionCode, $includeVoid, $branchId);

        if ($existingSaleTransaction) {
            return Rest::failed("Sale with the same OR number already exists");
        }

        $itemValidations = $this->productService->validateEnoughBranchStocks($branchId, $items);
        $valid = $this->productService->hasEnoughStocksByValidation($itemValidations);

        if (!$valid) {
            // FIXME: detailed message for which product variation id has not enough stocks
            return Rest::failed("Not enough stocks");
        }

        $priceRuleId = null;
        $customerUser = null;
        $customerId = null;

        $discountRemarks = null;

        if (isset($data['customer_id'])) {
            $customerId = $data['customer_id'];
            $customerUser = $this->userService->findByCustomerId($customerId);
        }

        if (isset($data['price_rule_id'])){
            $priceRuleId = $data['price_rule_id'];
        }

        if (isset($data['discount_remarks'])){
            $discountRemarks = $data['discount_remarks'];
        }

        $this->productService->subtractBranchStocks($branchId, $items);
        $this->productService->addSoldItemsCount($branchId, $items);
        $transactionDetails = $this->transactionService->createSaleTransaction($branchId, $staffId, $orNumber, $items, $priceRuleId);

        if (!isset($transactionDetails['transaction'])) {
            return Rest::failed("Something went wrong while creating transaction");
        }

        $transaction = $transactionDetails['transaction'];
        $transactionItems = $transactionDetails['items'];

        $transactionId = $transaction->id;

        if ($customerUser) {
            $discount = $this->transactionService->calculateDiscountByItems($transactionItems, StatusHelper::MEMBER, $priceRuleId);
            $this->transactionService->update($transactionId, [
                'customer_user_id' => $customerUser->id,
                'customer_id' => $customerId,
                'customer_firstname' => $customerUser->firstname,
                'customer_lastname' => $customerUser->lastname,
                'customer_phone' => $customerUser->phone,
                'discount' => $discount,
                'discount_remarks' => $discountRemarks
            ]);
            $this->transactionService->updateTransactionItemsDiscount($transactionId, StatusHelper::MEMBER, $priceRuleId);

        }

        if (!$customerUser) {
            $discount = $this->transactionService->calculateDiscountByItems($transactionItems, StatusHelper::GUEST, $priceRuleId);
            $this->transactionService->update($transactionId, [
                'customer_firstname' => $data['customer_firstname'],
                'customer_lastname' => $data['customer_lastname'],
                'discount' => $discount,
                'discount_remarks' => $discountRemarks
            ]);
            $this->transactionService->updateTransactionItemsDiscount($transactionId, StatusHelper::GUEST, $priceRuleId);
        }

        $this->activityService->logAddSale($staffId, $transactionId);
        $this->pusherService->triggerRefreshSaleEvent();
        $this->getProductAlertsByTransactionId($transactionId, $staffId);

        return Rest::success($transactionDetails);

    }

    public function returnSaleTransactionByBranchKey()
    {
        $payload = $this->payload;
        $data = $payload->all();

        // payload key and staff id are always needed for this controller
        // with the validations already found in middleware
        $key = $payload->key;
        $staffId = $payload->staff_id;

        $branch = $this->companyService->findBranchByKey($key);

        if (!$branch) {
            return Rest::notFound("Branch not found");
        }

        $branchId = $branch->id;

        $user = $this->companyService->findUserByStaffId($staffId);

        if (!$user) {
            return Rest::notFound("Staff not found.");
        }

        $canVoid = $user->can_void;

        if (!$canVoid) {
            return Rest::failed("Staff does not have permission to return items from this transaction");
        }

        $validator = $this->validator($data, [
            'or_no' => 'required',
            'items' => 'required'
        ]);

        if ($validator->fails()) {
            return Rest::validationFailed($validator);
        }

        if (empty($data['items'])) {
            return Rest::failed("Please input at least 1 Product.");
        }

        $items = $payload->items;
        $orNumber = $payload->or_no;

        $existingReturnSaleTransaction = $this->transactionService->findReturnSaleByOr($orNumber, $branchId);

        if ($existingReturnSaleTransaction) {
            return Rest::failed("Return sale with the same OR number already exists");
        }

        $saleTransactionCode = StatusHelper::SALE;
        $includeVoid = false;

        $existingSaleTransaction = $this->transactionService->findByOr($orNumber, $saleTransactionCode, $includeVoid, $branchId);

        if (!$existingSaleTransaction) {
            return Rest::failed("No sale transaction found");
        }

        $existingTransactionId = $existingSaleTransaction->id;
        $existingSaleTransactionItems = $existingSaleTransaction->items;

        $itemValidations = $this->transactionService->validateEnoughReturnStocks($existingSaleTransactionItems, $items);
        $valid = $this->productService->hasEnoughStocksByValidation($itemValidations);

        if (!$valid) {
            // FIXME: detailed message for which product variation id has not enough stocks
            return Rest::failed("Not enough stocks");
        }

        $this->productService->addBranchStocks($branchId, $items);
        $this->productService->subtractSoldItemsCount($branchId, $items);
        $transactionDetails = $this->transactionService->createReturnSaleTransaction($existingTransactionId, $branchId, $staffId, $orNumber, $items);

        if (!isset($transactionDetails['transaction'])) {
            return Rest::failed("Something went wrong while creating transaction");
        }

        $transaction = $transactionDetails['transaction'];
        $transactionId = $transaction['id'];

        $this->activityService->logReturnSale($staffId, $transactionId);
        $this->pusherService->triggerRefreshSaleEvent();

        return Rest::success($transactionDetails);
    }


    /* POS ADJUSTMENTS */

    public function adjustmentShortTransactionByBranchKey()
    {
        $payload = $this->payload;
        $data = $payload->all();

        // payload key and staff id are always needed for this controller
        // with the validations already found in middleware
        $key = $payload->key;
        $staffId = $payload->staff_id;

        $branch = $this->companyService->findBranchByKey($key);

        if (!$branch) {
            return Rest::notFound("Branch not found");
        }

        $branchId = $branch->id;

        $user = $this->companyService->findUserByStaffId($staffId);

        if (!$user) {
            return Rest::notFound("Staff not found.");
        }

        $canShort = $user->can_void;

        if (!$canShort) {
            return Rest::failed("Staff does not have permission");
        }

        $validator = $this->validator($data, [
            'items' => 'required',
            'remarks' => 'required'
        ]);

        if ($validator->fails()) {
            return Rest::validationFailed($validator);
        }

        if (empty($data['items'])) {
            return Rest::failed("Please input at least 1 Product.");
        }

        $items = $payload->items;

        $itemValidations = $this->productService->validateEnoughBranchStocks($branchId, $items);
        $valid = $this->productService->hasEnoughStocksByValidation($itemValidations);

        if (!$valid) {
            // FIXME: detailed message for which product variation id has not enough stocks
            return Rest::failed("Not enough stocks");
        }

        $this->productService->subtractBranchStocks($branchId, $items);
        $transactionDetails = $this->transactionService->createAdjustmentShortTransaction($branchId, $items, $staffId);

        if (!isset($transactionDetails['transaction'])) {
            return Rest::failed("Something went wrong while creating transaction");
        }

        $transaction = $transactionDetails['transaction'];
        $transactionId = $transaction['id'];

        $this->activityService->logAdjustmentShortByStaff($staffId, $transactionId);
        $this->pusherService->triggerNotificationToast("New short adjustment was created by Staff #.".$staffId);

        return Rest::success($transactionDetails);
    }

    public function voidAdjustmentShortTransactionByBranchKey($transactionId)
    {
        $payload = $this->payload;
        $data = $payload->all();

        $voidFlag = StatusHelper::VOID;
        $voidCode = StatusHelper::VOID_ADJUSTMENT_SHORT;
        $adjustmentShortCode = StatusHelper::ADJUSTMENT_SHORT;

        // payload key and staff id are always needed for this controller
        // with the validations already found in middleware
        $key = $payload->key;
        $staffId = $payload->staff_id;

        $branch = $this->companyService->findBranchByKey($key);

        if (!$branch) {
            return Rest::notFound("Branch not found");
        }

        $branchId = $branch->id;

        $user = $this->companyService->findUserByStaffId($staffId);

        if (!$user) {
            return Rest::notFound("Staff not found.");
        }

        $transaction = $this->transactionService->find($transactionId);

        if (!$transaction) {
            return Rest::notFound("Transaction not found.");
        }

        if ($transaction->branch_id != $branchId) {
            return Rest::failed("Cannot void this transaction as it does not belong to the branch that you're in.");
        }

        $transactionStatus = $transaction->status;
        if ($transactionStatus == $voidFlag) {
            return Rest::failed("Transaction already voided.");
        }

        $transactionType = $transaction->transaction_type;

        if ($transactionType !== $adjustmentShortCode) {
            return Rest::failed("Cannot void this transaction");
        }

        $canVoid = $user->can_void;

        if (!$canVoid) {
            return Rest::failed("Staff does not have permission to void this transaction");
        }

        $items = $this->transactionService->getTransactionItemsByTransactionId($transactionId);
        $this->productService->addBranchStocks($branchId, $items);
        $transactionDetails = $this->transactionService->voidAdjustmentShortTransaction($transactionId, $staffId);

        $this->activityService->logAdjustmentVoidShortByStaff($staffId, $transactionId);

        return Rest::success($transactionDetails);
    }

    public function adjustmentShortOverTransactionByBranchKey()
    {
        $payload = $this->payload;
        $data = $payload->all();

        $voidFlag = StatusHelper::VOID;

        // payload key and staff id are always needed for this controller
        // with the validations already found in middleware
        $key = $payload->key;
        $staffId = $payload->staff_id;

        $branch = $this->companyService->findBranchByKey($key);

        if (!$branch) {
            return Rest::notFound("Branch not found");
        }

        $branchId = $branch->id;

        $user = $this->companyService->findUserByStaffId($staffId);

        if (!$user) {
            return Rest::notFound("Staff not found.");
        }

        $canShort = $user->can_void;

        if (!$canShort) {
            return Rest::failed("Staff does not have permission");
        }

        $validator = $this->validator($data, [
            'or_no' => 'required',
            'items' => 'required',
            'remarks' => 'required'
        ]);

        if ($validator->fails()) {
            return Rest::validationFailed($validator);
        }

        if (empty($data['items'])) {
            return Rest::failed("Please input at least 1 Product.");
        }

        $items = $payload->items;
        $orNumber = $payload->or_no;

        $saleTransactionCode = StatusHelper::SALE;
        $includeVoid = false;

        $existingSaleTransaction = $this->transactionService->findByOr($orNumber, $saleTransactionCode, $includeVoid, $branchId);

        if (!$existingSaleTransaction) {
            return Rest::failed("Sale with OR Number does not exist");
        }

        $existingSaleTransactionStatus = $existingSaleTransaction->status;
        if ($existingSaleTransactionStatus == $voidFlag) {
            return Rest::failed("Cannot create an adjustment. Transaction already voided.");
        }

        $itemKeys = array_keys($items[0]);

        if (!in_array('product_variation_id', $itemKeys)) {
            return Rest::failed("Please input to be subtracted item");
        }

        if (!in_array('quantity', $itemKeys)) {
            return Rest::failed("Please input quantity");
        }

        if (!in_array('shortover_product_variation_id', $itemKeys)) {
            return Rest::failed("Please input shortover item");
        }

        $sameVariationValidation = $this->transactionService->validateShortOverOfSameId($items);
        $valid = $this->productService->hasEnoughStocksByValidation($sameVariationValidation);

        if (!$valid) {
            return Rest::failed("Product Variation must not be similar");
        }

        $branchStocksItemValidation = $this->productService->validateEnoughBranchStocks($branchId, $items);
        $valid = $this->productService->hasEnoughStocksByValidation($branchStocksItemValidation);

        if (!$valid) {
            // FIXME: detailed message for which product variation id has not enough stocks
            return Rest::failed("Not enough stocks to be subtracted from branch");
        }

        $existingShortOverTransactionItemValidation = $this->transactionService->validateEnoughShortOverItemQuantityByOr($orNumber, $items, $branchId);
        $valid = $this->productService->hasEnoughStocksByValidation($existingShortOverTransactionItemValidation);

        if (!$valid) {
            // FIXME: detailed message for which product variation id has not enough stocks
            return Rest::failed("Not enough stocks available to adjust from the transaction");
        }

        $this->productService->subtractBranchStocks($branchId, $items);
        $this->productService->addBranchStocksByShortOver($branchId, $items);
        $transactionDetails = $this->transactionService->createAdjustmentShortOverTransaction($orNumber, $branchId, $items, $staffId);

        if (!isset($transactionDetails['transaction'])) {
            return Rest::failed("Something went wrong while creating transaction");
        }

        $transaction = $transactionDetails['transaction'];
        $transactionId = $transaction['id'];

        $this->activityService->logAdjustmentShortOverByStaff($staffId, $transactionId);
        $this->pusherService->triggerNotificationToast("New short/over adjustment was created by Staff #.".$staffId);

        return Rest::success($transactionDetails);
    }

    public function voidAdjustmentShortOverTransactionByBranchKey($transactionId)
    {
        $payload = $this->payload;
        $data = $payload->all();

        $voidFlag = StatusHelper::VOID;
        $voidCode = StatusHelper::VOID_ADJUSTMENT_SHORTOVER;
        $adjustmentShortOverCode = StatusHelper::ADJUSTMENT_SHORTOVER;

        // payload key and staff id are always needed for this controller
        // with the validations already found in middleware
        $key = $payload->key;
        $staffId = $payload->staff_id;

        $branch = $this->companyService->findBranchByKey($key);

        if (!$branch) {
            return Rest::notFound("Branch not found");
        }

        $branchId = $branch->id;

        $user = $this->companyService->findUserByStaffId($staffId);

        if (!$user) {
            return Rest::notFound("Staff not found.");
        }

        $transaction = $this->transactionService->find($transactionId);

        if (!$transaction) {
            return Rest::notFound("Transaction not found.");
        }

        if ($transaction->branch_id != $branchId) {
            return Rest::failed("Cannot void this transaction as it does not belong to the branch that you're in.");
        }

        $transactionStatus = $transaction->status;
        if ($transactionStatus == $voidFlag) {
            return Rest::failed("Transaction already voided.");
        }

        $transactionType = $transaction->transaction_type;

        if ($transactionType !== $adjustmentShortOverCode) {
            return Rest::failed("Cannot void this transaction");
        }

        $canVoid = $user->can_void;

        if (!$canVoid) {
            return Rest::failed("Staff does not have permission to void this transaction");
        }

        $items = $this->transactionService->getTransactionItemsByTransactionId($transactionId);

        $this->productService->addBranchStocks($branchId, $items);
        $this->productService->subtractBranchStocksByShortOver($branchId, $items);

        $transactionDetails = $this->transactionService->voidAdjustmentShortOverTransaction($transactionId, $staffId);

        $this->activityService->logAdjustmentVoidShortOverByStaff($staffId, $transactionId);

        return Rest::success($transactionDetails);
    }


    /* POS SALE VOID */

    public function voidTransactionByBranchKey($transactionId)
    {
        $payload = $this->payload;
        $data = $payload->all();

        $voidFlag = StatusHelper::VOID;
        $voidCode = StatusHelper::VOID_SALE;
        $saleCode = StatusHelper::SALE;
        $adjustmentShortOverCode = StatusHelper::ADJUSTMENT_SHORTOVER;

        // payload key and staff id are always needed for this controller
        // with the validations already found in middleware
        $key = $payload->key;
        $staffId = $payload->staff_id;

        $branch = $this->companyService->findBranchByKey($key);

        if (!$branch) {
            return Rest::notFound("Branch not found");
        }

        $branchId = $branch->id;

        $user = $this->companyService->findUserByStaffId($staffId);

        if (!$user) {
            return Rest::notFound("Staff not found.");
        }

        $transaction = $this->transactionService->find($transactionId);

        if (!$transaction) {
            return Rest::notFound("Transaction not found.");
        }

        if ($transaction->branch_id != $branchId) {
            return Rest::failed("Cannot void this transaction as it does not belong to the branch that you're in.");
        }

        $transactionStatus = $transaction->status;

        if ($transactionStatus == $voidFlag) {
            return Rest::failed("Transaction already voided.");
        }

        $transactionType = $transaction->transaction_type;

        if ($transactionType !== $saleCode) {
            return Rest::failed("Cannot void this transaction");
        }

        $canVoid = $user->can_void;

        if (!$canVoid) {
            return Rest::failed("Staff does not have permission to void this transaction");
        }

        // check if has return sale
        $orNumber = $transaction->or_no;
        $returnSaleTransaction = $this->transactionService->findReturnSaleByOr($orNumber, $branchId);
        $shortOverTransactions = $this->transactionService->getByOr($orNumber, $adjustmentShortOverCode, $branchId);

        if ($returnSaleTransaction) {
            $returnSaleBranchId = $returnSaleTransaction->branch_id;
            $returnSaleItems = $this->transactionService->getTransactionItemsByTransactionId($returnSaleTransaction->id);
            $this->productService->subtractBranchStocks($returnSaleBranchId, $returnSaleItems);
            $this->transactionService->voidReturnSaleTransaction($returnSaleTransaction->id, $staffId);
        }

        if (!empty($shortOverTransactions)) {
            foreach ($shortOverTransactions as $shortOverTransaction) {

                $shortOverTransactionId = $shortOverTransaction->id;

                $shortOverItems = $this->transactionService->getTransactionItemsByTransactionId($shortOverTransactionId);

                $this->productService->addBranchStocks($branchId, $shortOverItems);
                $this->productService->subtractBranchStocksByShortOver($branchId, $shortOverItems);

                $this->transactionService->voidAdjustmentShortOverTransaction($shortOverTransactionId, $staffId);
            }
        }

        $items = $this->transactionService->getTransactionItemsByTransactionId($transactionId);

        $this->productService->addBranchStocks($branchId, $items);
        $transactionDetails = $this->transactionService->voidSaleTransaction($transactionId, $staffId);

        $this->activityService->logVoidSaleByStaff($staffId, $transactionId);

        return Rest::success($transactionDetails, [
            'shortovers' => $shortOverTransactions
        ]);


    }

    public function voidReturnSaleTransactionByBranchKey($transactionId)
    {
        $payload = $this->payload;
        $data = $payload->all();

        $voidFlag = StatusHelper::VOID;
        $voidCode = StatusHelper::VOID_RETURN;
        $returnSaleCode = StatusHelper::RETURN_SALE;

        // payload key and staff id are always needed for this controller
        // with the validations already found in middleware
        $key = $payload->key;
        $staffId = $payload->staff_id;

        $branch = $this->companyService->findBranchByKey($key);

        if (!$branch) {
            return Rest::notFound("Branch not found");
        }

        $branchId = $branch->id;

        $user = $this->companyService->findUserByStaffId($staffId);

        if (!$user) {
            return Rest::notFound("Staff not found.");
        }

        $transaction = $this->transactionService->find($transactionId);

        if (!$transaction) {
            return Rest::notFound("Transaction not found.");
        }

        if ($transaction->branch_id != $branchId) {
            return Rest::failed("Cannot void this transaction as it does not belong to the branch that you're in.");
        }

        // check if has return sale
        $orNumber = $transaction->or_no;
        $returnSaleTransaction = $this->transactionService->findReturnSaleByOr($orNumber, $branchId);

        if (!$returnSaleTransaction) {
            return Rest::failed("Cannot void return sale for this transaction. Transaction does not have a return sale");
        }

        $transactionStatus = $transaction->status;
        if ($transactionStatus == $voidFlag) {
            return Rest::failed("Transaction already voided.");
        }

        $returnSaleTransactionId = $returnSaleTransaction->id;
        $returnSaleTransactionType = $returnSaleTransaction->transaction_type;

        if ($returnSaleTransactionType !== $returnSaleCode) {
            return Rest::failed("Cannot void this transaction");
        }

        $canVoid = $user->can_void;

        if (!$canVoid) {
            return Rest::failed("Staff does not have permission to void this transaction");
        }

        $items = $this->transactionService->getTransactionItemsByTransactionId($returnSaleTransactionId);

        $this->productService->subtractBranchStocks($branchId, $items);
        $transactionDetails = $this->transactionService->voidReturnSaleTransaction($returnSaleTransactionId, $staffId);

        $this->activityService->logVoidReturnSaleByStaff($staffId, $returnSaleTransactionId);

        return Rest::success($transactionDetails);
    }


    /* DISCOUNTS */

    public function calculateDiscountFromBranch()
    {
        $payload = $this->payload;
        $data = $payload->all();

        $key = $payload->key;

        $guest = StatusHelper::GUEST;
        $member = StatusHelper::MEMBER;

        $branch = $this->companyService->findBranchByKey($key);

        if (!$branch) {
            return Rest::notFound("Branch not found");
        }

        $validator = $this->validator($data, [
            'items' => 'required'
        ]);

        if ($validator->fails()) {
            return Rest::validationFailed($validator);
        }

        $items = $data['items'];
        $priceRuleId = null;

        if (!is_array($items)) {
            return Rest::failed("Please input items correctly");
        }

        if(isset($data['price_rule_id'])){
            $priceRuleId = $data['price_rule_id'];
        }

        if (!isset($data['customer_id'])) {

            $discount = $this->transactionService->calculateDiscountByItems($items, $guest, $priceRuleId);
            return Rest::success($discount);

        }

        $customerId = $data['customer_id'];

        if ($customerId == null || trim($customerId) == '') {

            $discount = $this->transactionService->calculateDiscountByItems($items, $guest, $priceRuleId);
            return Rest::success($discount);

        }

        $discount = $this->transactionService->calculateDiscountByItems($items, $member, $priceRuleId);
        return Rest::success($discount);
    }


    /* PRIVATE functions */

    private function regCustomer()
    {
        /*
         if (!isset($data['customer_id']) && isset($data['firstname']) && isset($data['lastname'])) {

             $role = StatusHelper::MEMBER;

             $phone = null;

             if(isset($data['phone'])){
                 $phone = $data['phone'];
             }

             $customerUser = $this->userService->create([
                 'firstname' => $data['firstname'],
                 'lastname' => $data['lastname'],
                 'phone' => $phone,
                 'role' => $role,
             ]);

             $this->userService->generateCustomerId($customerUser->id);

         }
         */
    }

    private function voidSaleTransaction($transactionId)
    {
        $userId = $this->loggedInUserId;

        $transaction = $this->transactionService->find($transactionId);

        if (!$transaction) {
            return Rest::notFound("Transaction not found.");
        }

        $branchId = $transaction->branch_id;

        // check if has return sale
        $orNumber = $transaction->or_no;
        $returnSaleTransaction = $this->transactionService->findReturnSaleByOr($orNumber, $branchId);

        if ($returnSaleTransaction) {
            $returnSaleBranchId = $returnSaleTransaction->branch_id;
            $returnSaleItems = $this->transactionService->getTransactionItemsByTransactionId($returnSaleTransaction->id);
            $this->productService->subtractBranchStocks($returnSaleBranchId, $returnSaleItems);
            $this->transactionService->voidReturnSaleTransaction($returnSaleTransaction->id, null, $userId);
        }

        $items = $this->transactionService->getTransactionItemsByTransactionId($transactionId);

        $branchId = $transaction->branch_id;
        $this->productService->addBranchStocks($branchId, $items);
        $this->productService->subtractSoldItemsCount($branchId, $items);
        $transactionDetails = $this->transactionService->voidSaleTransaction($transactionId, null, $userId);

        $this->pusherService->triggerRefreshSaleEvent();
        $this->activityService->logVoidSale($userId, $transactionId);

        return Rest::success($transactionDetails);
    }

    private function voidReturnSaleTransaction($transactionId)
    {
        $userId = $this->loggedInUserId;

        $transaction = $this->transactionService->find($transactionId);

        if (!$transaction) {
            return Rest::notFound("Transaction not found.");
        }

        $items = $this->transactionService->getTransactionItemsByTransactionId($transactionId);

        $branchId = $transaction->branch_id;
        $this->productService->subtractBranchStocks($branchId, $items);
        $this->productService->addSoldItemsCount($branchId, $items);
        $transactionDetails = $this->transactionService->voidReturnSaleTransaction($transactionId, null, $userId);

        $this->pusherService->triggerRefreshSaleEvent();
        $this->activityService->logVoidReturnSale($userId, $transactionId);

        return Rest::success($transactionDetails);
    }

    private function voidAdjustmentShortTransaction($transactionId)
    {
        $userId = $this->loggedInUserId;

        $voidFlag = StatusHelper::VOID;
        $adjustmentShortCode = StatusHelper::ADJUSTMENT_SHORT;

        $transaction = $this->transactionService->find($transactionId);

        if (!$transaction) {
            return Rest::notFound("Transaction not found.");
        }

        $branchId = $transaction->branch_id;

        $transactionStatus = $transaction->status;
        if ($transactionStatus == $voidFlag) {
            return Rest::failed("Transaction already voided.");
        }

        $transactionType = $transaction->transaction_type;

        if ($transactionType !== $adjustmentShortCode) {
            return Rest::failed("Cannot void this transaction");
        }

        $items = $this->transactionService->getTransactionItemsByTransactionId($transactionId);
        $this->productService->addBranchStocks($branchId, $items);
        $transactionDetails = $this->transactionService->voidAdjustmentShortTransaction($transactionId, null, $userId);

        $this->activityService->logAdjustmentVoidShort($userId, $transactionId);

        return Rest::success($transactionDetails);
    }

    private function voidAdjustmentShortOverTransaction($transactionId)
    {
        $userId = $this->loggedInUserId;

        $voidFlag = StatusHelper::VOID;
        $adjustmentShortOverCode = StatusHelper::ADJUSTMENT_SHORTOVER;

        $transaction = $this->transactionService->find($transactionId);

        if (!$transaction) {
            return Rest::notFound("Transaction not found.");
        }

        $branchId = $transaction->branch_id;

        $transactionStatus = $transaction->status;
        if ($transactionStatus == $voidFlag) {
            return Rest::failed("Transaction already voided.");
        }

        $transactionType = $transaction->transaction_type;

        if ($transactionType !== $adjustmentShortOverCode) {
            return Rest::failed("Cannot void this transaction");
        }

        $items = $this->transactionService->getTransactionItemsByTransactionId($transactionId);

        $this->productService->addBranchStocks($branchId, $items);
        $this->productService->subtractBranchStocksByShortOver($branchId, $items);

        $transactionDetails = $this->transactionService->voidAdjustmentShortOverTransaction($transactionId, null, $userId);

        $this->activityService->logAdjustmentVoidShortOver($userId, $transactionId);

        return Rest::success($transactionDetails);
    }

    private function getProductAlertsByTransactionId($transactionId, $staffId)
    {
        $inventoryStatusLow = StatusHelper::INVENTORY_STATUS_LOW;
        $inventoryStatusSoldOut = StatusHelper::INVENTORY_STATUS_SOLD_OUT;

        $transaction = $this->transactionService->find($transactionId);

        if(!$transaction){
            return false;
        }

        $branchId = $transaction->branch_id;
        $items = $this->transactionService->getTransactionItemsByTransactionId($transactionId);


        $threshold = $this->companyService->getDefaultLowInventoryThresholdByBranchId($branchId);
        $alerts = $this->productService->getAlertsByItems($items, $threshold);

        foreach($alerts as $alert){

            $productVariationId = $alert->product_variation_id;
            $alertStatus = $alert->inventory_status;
            $alertCode = $alert->inventory_status_code;
            $productName = $alert->product_name;

            switch($alertCode){
                case $inventoryStatusSoldOut:
                    $this->activityService->logInventoryLowByStaff($productName." - ".$alertStatus, $staffId, $branchId);
                    $this->pusherService->triggerNotification();
                    $this->pusherService->triggerNotificationToast($productName." - ".$alertStatus, "NOTIFICATION", 'danger');
                    break;
                case $inventoryStatusLow:
                    $this->activityService->logInventoryLowByStaff($productName." - ".$alertStatus, $staffId, $branchId);
                    $this->pusherService->triggerNotification();
                    $this->pusherService->triggerNotificationToast($productName." - ".$alertStatus, "NOTIFICATION", 'warning');
                    break;
            }

        }

        if(count($alerts)>0){

        }


    }


    /* STILL DISCUSSED functions */

    public function shortSaleTransactionByBranchKey()
    {
        $payload = $this->payload;
        $data = $payload->all();

        // payload key and staff id are always needed for this controller
        // with the validations already found in middleware
        $key = $payload->key;
        $staffId = $payload->staff_id;

        $branch = $this->companyService->findBranchByKey($key);

        if (!$branch) {
            return Rest::notFound("Branch not found");
        }

        $branchId = $branch->id;

        $user = $this->companyService->findUserByStaffId($staffId);

        if (!$user) {
            return Rest::notFound("Staff not found.");
        }

        $canVoid = $user->can_void;

        if (!$canVoid) {
            return Rest::failed("Staff does not have permission to return items from this transaction");
        }

        $validator = $this->validator($data, [
            'or_no' => 'required',
            'items' => 'required'
        ]);

        if ($validator->fails()) {
            return Rest::validationFailed($validator);
        }

        if (empty($data['items'])) {
            return Rest::failed("Please input at least 1 Product.");
        }

        $items = $payload->items;
        $orNumber = $payload->or_no;

        $includeVoid = false;
        $saleTransaction = StatusHelper::SALE;

        $existingShortSaleTransaction = $this->transactionService->findByOr($orNumber, StatusHelper::SHORT_SALE, $includeVoid, $branchId);

        if ($existingShortSaleTransaction) {
            return Rest::failed("Short sale with the same OR number already exists");
        }

        $existingSaleTransaction = $this->transactionService->findByOr($orNumber, $saleTransaction, $includeVoid, $branchId);

        if (!$existingSaleTransaction) {
            return Rest::failed("No sale transaction found");
        }

        $existingTransactionId = $existingSaleTransaction->id;
        $existingSaleTransactionItems = $existingSaleTransaction->items;

        $itemValidations = $this->transactionService->validateEnoughReturnStocks($existingSaleTransactionItems, $items);
        $valid = $this->productService->hasEnoughStocksByValidation($itemValidations);

        if (!$valid) {
            // FIXME: detailed message for which product variation id has not enough stocks
            return Rest::failed("Not enough stocks");
        }

        $this->productService->addBranchStocks($branchId, $items);
        $transactionDetails = $this->transactionService->createShortSaleTransaction($existingTransactionId, $branchId, $staffId, $orNumber, $items);

        if (!isset($transactionDetails['transaction'])) {
            return Rest::failed("Something went wrong while creating transaction");
        }

        //$this->activityService->logShortSale($staffId);

        return Rest::success($transactionDetails);
    }

    public function shortoverSaleTransactionByBranchKey()
    {
        $payload = $this->payload;
        $data = $payload->all();

        // payload key and staff id are always needed for this controller
        // with the validations already found in middleware
        $key = $payload->key;
        $staffId = $payload->staff_id;

        $branch = $this->companyService->findBranchByKey($key);

        if (!$branch) {
            return Rest::notFound("Branch not found");
        }

        $branchId = $branch->id;

        $user = $this->companyService->findUserByStaffId($staffId);

        if (!$user) {
            return Rest::notFound("Staff not found.");
        }

        $canVoid = $user->can_void;

        if (!$canVoid) {
            return Rest::failed("Staff does not have permission to return items from this transaction");
        }

        $validator = $this->validator($data, [
            'or_no' => 'required',
            'items' => 'required'
        ]);

        if ($validator->fails()) {
            return Rest::validationFailed($validator);
        }

        if (empty($data['items'])) {
            return Rest::failed("Please input at least 1 Product.");
        }

        $items = $payload->items;
        $orNumber = $payload->or_no;

        $saleTransactionCode = StatusHelper::SALE;
        $includeVoid = false;

        $existingShortoverSaleTransaction = $this->transactionService->findByOr($orNumber, StatusHelper::SHORTOVER_SALE, $includeVoid, $branchId);

        if ($existingShortoverSaleTransaction) {
            return Rest::failed("Shortover sale with the same OR number already exists");
        }

        $existingSaleTransaction = $this->transactionService->findByOr($orNumber, $saleTransactionCode, $includeVoid, $branchId);

        if (!$existingSaleTransaction) {
            return Rest::failed("No sale transaction found");
        }

        $existingTransactionId = $existingSaleTransaction->id;
        $existingSaleTransactionItems = $existingSaleTransaction->items;

        $itemValidations = $this->transactionService->validateEnoughReturnStocks($existingSaleTransactionItems, $items);
        $valid = $this->productService->hasEnoughStocksByValidation($itemValidations);

        if (!$valid) {
            // FIXME: detailed message for which product variation id has not enough stocks
            return Rest::failed("Not enough stocks");
        }

        $this->productService->addBranchStocks($branchId, $items);
        $transactionDetails = $this->transactionService->createShortoverSaleTransaction($existingTransactionId, $branchId, $staffId, $orNumber, $items);

        if (!isset($transactionDetails['transaction'])) {
            return Rest::failed("Something went wrong while creating transaction");
        }

        //$this->activityService->logShortoverSale($staffId);

        return Rest::success($transactionDetails);
    }

    public function voidShortSaleTransactionByBranchKey($transactionId)
    {
        $payload = $this->payload;
        $data = $payload->all();

        $voidFlag = StatusHelper::VOID;
        $voidCode = StatusHelper::VOID_SHORT;
        $shortSaleCode = StatusHelper::SHORT_SALE;

        // payload key and staff id are always needed for this controller
        // with the validations already found in middleware
        $key = $payload->key;
        $staffId = $payload->staff_id;

        $branch = $this->companyService->findBranchByKey($key);

        if (!$branch) {
            return Rest::notFound("Branch not found");
        }

        $branchId = $branch->id;

        $user = $this->companyService->findUserByStaffId($staffId);

        if (!$user) {
            return Rest::notFound("Staff not found.");
        }

        $transaction = $this->transactionService->find($transactionId);

        if (!$transaction) {
            return Rest::notFound("Transaction not found.");
        }

        if ($transaction->branch_id != $branchId) {
            return Rest::failed("Cannot void this transaction as it does not belong to the branch that you're in.");
        }

        $includeVoid = false;

        // check if has return sale
        $orNumber = $transaction->or_no;
        $shortSaleTransaction = $this->transactionService->findByOr($orNumber, $shortSaleCode, $includeVoid, $branchId);

        if (!$shortSaleTransaction) {
            return Rest::failed("Cannot void short sale for this transaction. Transaction does not have a short sale");
        }

        $transactionStatus = $transaction->status;
        if ($transactionStatus == $voidFlag) {
            return Rest::failed("Transaction already voided.");
        }

        $shortSaleTransactionId = $shortSaleTransaction->id;
        $shortSaleTransactionType = $shortSaleTransaction->transaction_type;

        if ($shortSaleTransactionType !== $shortSaleCode) {
            return Rest::failed("Cannot void this transaction");
        }

        $canVoid = $user->can_void;

        if (!$canVoid) {
            return Rest::failed("Staff does not have permission to void this transaction");
        }

        $items = $this->transactionService->getTransactionItemsByTransactionId($shortSaleTransactionId);

        $this->productService->subtractBranchStocks($branchId, $items);
        $transactionDetails = $this->transactionService->voidShortSaleTransaction($shortSaleTransactionId, $staffId);

        //$this->activityService->logVoidShortSale($staffId);

        return Rest::success($transactionDetails);
    }

    public function voidShortoverSaleTransactionByBranchKey($transactionId)
    {
        $payload = $this->payload;
        $data = $payload->all();

        $voidFlag = StatusHelper::VOID;
        $voidCode = StatusHelper::VOID_SHORTOVER;
        $shortoverSaleCode = StatusHelper::SHORTOVER_SALE;

        // payload key and staff id are always needed for this controller
        // with the validations already found in middleware
        $key = $payload->key;
        $staffId = $payload->staff_id;

        $branch = $this->companyService->findBranchByKey($key);

        if (!$branch) {
            return Rest::notFound("Branch not found");
        }

        $branchId = $branch->id;

        $user = $this->companyService->findUserByStaffId($staffId);

        if (!$user) {
            return Rest::notFound("Staff not found.");
        }

        $transaction = $this->transactionService->find($transactionId);

        if (!$transaction) {
            return Rest::notFound("Transaction not found.");
        }

        if ($transaction->branch_id != $branchId) {
            return Rest::failed("Cannot void this transaction as it does not belong to the branch that you're in.");
        }

        $includeVoid = false;

        // check if has return sale
        $orNumber = $transaction->or_no;
        $shortoverSaleTransaction = $this->transactionService->findByOr($orNumber, $shortoverSaleCode, $includeVoid, $branchId);

        if (!$shortoverSaleTransaction) {
            return Rest::failed("Cannot void shortover sale for this transaction. Transaction does not have a shortover sale");
        }

        $transactionStatus = $transaction->status;
        if ($transactionStatus == $voidFlag) {
            return Rest::failed("Transaction already voided.");
        }

        $shortoverSaleTransactionId = $shortoverSaleTransaction->id;
        $shortoverSaleTransactionType = $shortoverSaleTransaction->transaction_type;

        if ($shortoverSaleTransactionType !== $shortoverSaleCode) {
            return Rest::failed("Cannot void this transaction");
        }

        $canVoid = $user->can_void;

        if (!$canVoid) {
            return Rest::failed("Staff does not have permission to void this transaction");
        }

        $items = $this->transactionService->getTransactionItemsByTransactionId($shortoverSaleTransactionId);

        $this->productService->subtractBranchStocks($branchId, $items);
        $transactionDetails = $this->transactionService->voidShortoverSaleTransaction($shortoverSaleTransactionId, $staffId);

        //$this->activityService->logVoidShortoverSale($staffId);

        return Rest::success($transactionDetails);
    }

    public function export()
    {
        $data = $this->payload->all();

        $export = $this->exportService->export($data);

        if(!$export) {
            return Rest::failed("Data might not exist on the database. Please try again");
        }

        $path = url('uploads/exports/'.$export);

        return Rest::success($path);
    }


    /* WAREHOUSE */

    public function getWarehouseLedger()
    {
        $payload = $this->payload;
        $filter = $payload->all();

        $ledger = $this->transactionService->getWarehouseLedger($filter);
        $ledgerMeta = $this->transactionService->getWarehouseLedgerMeta($filter);

        return Rest::success($ledger, $ledgerMeta);
    }

    public function getBranchLedger()
    {
        $payload = $this->payload;
        $filter = $payload->all();

        $ledger = $this->transactionService->getBranchLedger($filter);
        $ledgerMeta = $this->transactionService->getBranchLedgerMeta($filter);

        return Rest::success($ledger, $ledgerMeta);

    }


    /* SYNC FUNCTIONS */

    public function uploadNewTransaction()
    {
        $payload = $this->payload;
        $data = $payload->all();

        $validator = $this->validator($data, [
            'or_no' => 'required',
            'branch_id' => 'required',
            'items' => 'required'
        ]);

        if ($validator->fails()) {
            return Rest::validationFailed($validator);
        }

        $orNumber = $data['or_no'];
        $branchId = $data['branch_id'];
        $transactionTypeId = $data['transaction_type_id'];
        $staffId = $data['staff_id'];

        /*$existingTransaction = $this->transactionService->findByOrAndTransactionTypeId($orNumber, $transactionTypeId, $branchId);

        if($existingTransaction){
            return Rest::failed("There is already an existing transaction with the same information.");
        }*/

        $transactionItemsData = $data['items'];
        unset($data['items']);

        $transaction = $this->transactionService->create($data);

        if(!$transaction){
            return Rest::failed("Something went wrong while creating transaction");
        }

        $transactionId = $transaction->id;
        //$transactionTypeId = $transaction->transaction_type_id;

        $updated = $this->transactionService->updateTransactionStatusByORAndType($orNumber, $transactionTypeId, $branchId);

        if(!$updated){
            return Rest::failed("Something went wrong while updating transaction status by or and type.");
        }

        $transactionItems = $this->transactionService->createMultipleTransactionItems($transactionItemsData, $transactionId);

        if(!$transactionItems){
            return Rest::failed("Something wrong while creating transaction items. Transaction was created prior to that.");
        }

        $this->productService->updateBranchStocksByTransactionDetails($branchId, $transactionTypeId, $transactionItems);
        $this->activityService->logActivityByTransactionDetails($transactionId, $staffId, $transactionTypeId);
        $this->pusherService->triggerRefreshSaleEvent();

        return Rest::success($transaction, ['items'=>$transactionItems]);

    }

}
