<?php

namespace App\DTIStore\Api\Controllers;

use App\DTIStore\Helpers\Rest;
use App\DTIStore\Helpers\StatusHelper;
use App\DTIStore\Services\ActivityService;
use App\DTIStore\Services\StoreService;
use App\DTIStore\Services\ProductService;
use App\DTIStore\Services\TransactionService;
use App\DTIStore\Services\ExportService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    protected $productService;
    protected $transactionService;
    protected $companyService;
    protected $activityService;
    protected $exportService;

    public function __construct(
        Request $request,
        ProductService $productService,
        TransactionService $transactionService,
        StoreService $companyService,
        ActivityService $activityService,
        ExportService $exportService
    )
    {
        parent::__construct($request);

        $this->productService = $productService;
        $this->transactionService = $transactionService;
        $this->companyService = $companyService;
        $this->activityService = $activityService;
        $this->exportService = $exportService;

    }

    public function getAll()
    {
        $payload = $this->payload;
        $data = $payload->all();

        $productsMeta = $this->productService->getFilterMeta($data);
        $products = $this->productService->filter($data);

        return Rest::success($products, $productsMeta);
    }

    public function getByBranchKey()
    {
        // key checks are done in the middleware so don't worry

        $payload = $this->payload;
        $data = $payload->all();
        $key = $payload->key;

        $branch = $this->companyService->findBranchByKey($key);
        $branchId = $branch->id;

        $data['branch_id'] = $branchId;
        $productsMeta = $this->productService->getFilterMeta($data);
        $products = $this->productService->filter($data);

        return Rest::success($products, $productsMeta);

    }

    public function getVatByBranchKey()
    {
        // key checks are done in the middleware so don't worry

        $payload = $this->payload;
        $data = $payload->all();
        $key = $payload->key;

        $branch = $this->companyService->findBranchByKey($key);
        $branchId = $branch->id;

        $vat = $this->companyService->getDefaultVatByBranchId($branchId);

        return Rest::success($vat);

    }

    public function getAllPriceRules()
    {
        $priceRules = $this->productService->getAllPriceRules();

        return Rest::success($priceRules);
    }

    public function getLowInventoryThresholdByBranchKey()
    {
        // key checks are done in the middleware so don't worry

        $payload = $this->payload;
        $data = $payload->all();
        $key = $payload->key;

        $branch = $this->companyService->findBranchByKey($key);
        $branchId = $branch->id;

        $threshold = $this->companyService->getDefaultLowInventoryThresholdByBranchId($branchId);

        return Rest::success($threshold);

    }

    public function getBranchStocksById($branchId)
    {
        $payload = $this->payload;
        $filter = $payload->all();

        $branchStocks = $this->productService->getBranchStocksById($branchId, $filter);

        return Rest::success($branchStocks);
    }

    public function getCompanyStocks()
    {
        $payload = $this->payload;
        $filter = $payload->all();

        $companyStocks = $this->productService->getCompanyStocks($filter);

        return Rest::success($companyStocks);
    }

    public function create()
    {
        $payload = $this->payload;
        $data = $payload->all();

        $validator = $this->validator($data, [
            'name' => 'required|unique:products',
            'code' => 'required|unique:products',
            'product_category_id' => 'required|exists:product_categories,id'
        ]);

        if ($validator->fails()) {
            return Rest::validationFailed($validator);
        }

        // TODO: validate variations before creating

        $product = $this->productService->create($data);

        if (!$product) {
            return Rest::failed("Something went wrong while creating new product");
        }

        $productId = $product->id;

        if (!isset($data['variations'])) {

            $product = $this->productService->find($productId);
            return Rest::success($product);

        }


        $variations = $data['variations'];
        $variations = $this->productService->createProductVariations($productId, $variations);

        $valid = $this->productService->hasEnoughStocksByValidation($variations);

        if (!$valid) {
            return Rest::failed("Something went wrong while inserting product variations");
        }

        $this->productService->addCompanyStocksByVariations($variations);

        $product = $this->productService->find($productId);

        //$this->activityService->logCreateProduct($this->loggedInUserId);

        return Rest::success($product);


    }

    public function update($id)
    {
        $payload = $this->payload;
        $data = $payload->all();

        $product = $this->productService->find($id);

        if (!$product) {
            return Rest::notFound("Product not found.");
        }

        $validator = $this->validator($data, [
            'name' => 'unique:products,name,' . $id,
            'code' => 'unique:products,code,' . $id,
        ]);

        if ($validator->fails()) {
            return Rest::validationFailed($validator);
        }

        $isDeleted = $this->productService->isDeleted($id);

        if ($isDeleted) {
            return Rest::notFound("Product not found.");
        }

        $updated = $this->productService->update($id, $data);
        $product = $this->productService->find($id);

        //$this->activityService->logUpdateProduct($this->loggedInUserId);

        return Rest::updateSuccess($updated, $product);
    }

    public function delete($id)
    {
        $deleted = $this->productService->delete($id);

        //$this->activityService->logDeleteProduct($this->loggedInUserId);

        return Rest::deleteSuccess($deleted);
    }

    public function uploadImage($id)
    {
        $payload = $this->payload;
        $data = $payload->all();

        $validator = $this->validator($data, [
            'image' => 'image',
        ]);

        if ($validator->fails()) {
            return Rest::validationFailed($validator);
        }

        $product = $this->productService->find($id);

        if (!$product) {
            return Rest::notFound("Product not found.");
        }

        if (!$payload->hasFile('image')) {
            return Rest::notFound("Image not found.", $data);
        }

        $image = $payload->file('image');
        $imageFileName = time() . '_' . $image->getClientOriginalName();
        $uploadPath = public_path('/uploads');

        $image->move($uploadPath, $imageFileName);

        $updated = $this->productService->update($id, [
            'image_url' => 'uploads/' . $imageFileName
        ]);

        //$product = $this->productService->find($id);

        return Rest::updateSuccess($updated);
    }

    public function getAlerts()
    {
        $payload = $this->payload;
        $data = $payload->all();

        $branchId = null;

        if (isset($data['branch_id'])) {
            $branchId = $data['branch_id'];
        }

        $threshold = $this->companyService->getDefaultLowInventoryThresholdByBranchId($branchId);
        $alerts = $this->productService->getAlerts($data, $threshold);

        return Rest::success($alerts);
    }

    public function addStocksByProductId($productId)
    {
        $payload = $this->payload;
        $data = $payload->all();

        $product = $this->productService->find($productId);

        if (!$product) {
            return Rest::notFound("Product not found");
        }

        $validator = $this->validator($data, [
            'quantity' => 'required',
        ]);

        if ($validator->fails()) {
            return Rest::validationFailed($validator);
        }

        $userId = $this->loggedInUserId;
        $quantity = $payload->quantity;

        $added = $this->productService->addStocksByProductId($productId, $quantity);
        $transactions = $this->transactionService->addStocksByProductId($productId, $quantity, $userId);

        return Rest::updateSuccess($added, $transactions);
    }

    public function subtractStocksByProductId($productId)
    {
        $payload = $this->payload;
        $data = $payload->all();

        $product = $this->productService->find($productId);

        if (!$product) {
            return Rest::notFound("Product not found");
        }

        $validator = $this->validator($data, [
            'quantity' => 'required',
        ]);

        if ($validator->fails()) {
            return Rest::validationFailed($validator);
        }

        $userId = $this->loggedInUserId;
        $quantity = $payload->quantity;

        $subtracted = $this->productService->subtractStocksByProductId($productId, $quantity);
        $transactions = $this->transactionService->subtractStocksByProductId($productId, $quantity, $userId);

        return Rest::updateSuccess($subtracted, $transactions);
    }

    public function addStocksByVariationId($variationId)
    {
        $payload = $this->payload;
        $data = $payload->all();

        $variation = $this->productService->findProductVariation($variationId);

        if (!$variation) {
            return Rest::notFound("Variation not found");
        }

        $validator = $this->validator($data, [
            'quantity' => 'required',
        ]);

        if ($validator->fails()) {
            return Rest::validationFailed($validator);
        }

        $userId = $this->loggedInUserId;
        $quantity = $payload->quantity;

        $remarks = null;

        if(isset($data['remarks'])){
            $remarks = $data['remarks'];
        }

        $added = $this->productService->addStocksByVariationId($variationId, $quantity);
        $transactions = $this->transactionService->addStocksByVariationId($variationId, $quantity, $userId, $remarks);

        //$this->activityService->logRestock($userId);

        return Rest::updateSuccess($added, $transactions);
    }

    public function subtractStocksByVariationId($variationId)
    {
        $payload = $this->payload;
        $data = $payload->all();

        $variation = $this->productService->findProductVariationWithCompanyStocks($variationId);

        if (!$variation) {
            return Rest::notFound("Variation not found");
        }

        $validator = $this->validator($data, [
            'quantity' => 'required',
        ]);

        if ($validator->fails()) {
            return Rest::validationFailed($validator);
        }

        $userId = $this->loggedInUserId;
        $quantity = $payload->quantity;

        $companyQuantity = $variation->company_quantity;

        if ($companyQuantity <= 0 || $quantity > $companyQuantity) {
            return Rest::failed("Not enough stocks to subtract");
        }

        $remarks = null;

        if(isset($data['remarks'])){
            $remarks = $data['remarks'];
        }

        $added = $this->productService->subtractStocksByVariationId($variationId, $quantity);
        $transactions = $this->transactionService->subtractStocksByVariationId($variationId, $quantity, $userId, $remarks);

        //$this->activityService->logRestock($userId);

        return Rest::updateSuccess($added, $transactions);
    }

    public function getDeliveryWithItems($deliveryId)
    {
        $delivery = $this->productService->findDelivery($deliveryId);

        if (!$delivery) {
            return Rest::notFound("Delivery not found.");
        }

        return Rest::success($delivery);
    }

    public function voidPendingDeliveryById($deliveryId)
    {
        $delivery = $this->productService->findDelivery($deliveryId);

        if (!$delivery) {
            return Rest::notFound("Delivery not found.");
        }

        $currentDeliveryStatus = $delivery->status;

        if ($currentDeliveryStatus != StatusHelper::PENDING) {
            return Rest::failed("This delivery cannot be voided because it is not a pending delivery.");
        }

        $updated = $this->productService->voidDelivery($deliveryId);

        if (!$updated) {
            return Rest::failed("Something went wrong while updating delivery");
        }

        $delivery = $this->productService->findDelivery($deliveryId);

        //$this->activityService->logVoidPendingDelivery($this->loggedInUserId);

        return Rest::updateSuccess($updated, $delivery);

    }

    public function createPendingDeliveries()
    {
        $payload = $this->payload;
        $data = $payload->all();

        $validator = $this->validator($data, [
            'branch_id' => 'required',
            'deliveries' => 'required',
        ]);

        if ($validator->fails()) {
            return Rest::validationFailed($validator);
        }

        $branchId = $payload->branch_id;
        $deliveries = $payload->deliveries;
        $remarks = "";
        $invoiceNo = "";

        if (empty($deliveries)) {
            return Rest::failed("Cannot deliver items. Please input at least 1 product and quantity.");
        }

        $isFranchisee = $this->companyService->isBranchFranchisee($branchId);

        if (isset($data['remarks'])) {
            $remarks = $data['remarks'];
        }

        if (isset($data['invoice_no'])) {
            $invoiceNo = $data['invoice_no'];
        }

        if ($isFranchisee && ($invoiceNo == "" || $invoiceNo == null)) {
            return Rest::failed("Cannot deliver items. Please input valid invoice number.");
        }

        //$deliveryValidations = $this->productService->validateStocksDelivery($deliveries);
        //$hasEnoughStocks = $this->productService->hasEnoughStocksByValidation($deliveryValidations);

        //if (!$hasEnoughStocks) {
        //    return Rest::failed("Not enough Stocks", $deliveryValidations);
        //}

        return $this->createPendingDeliveriesByBranchId($branchId, $deliveries, $remarks, $invoiceNo);

        // TODO: create activity log
    }

    public function createConfirmedDeliveries()
    {
        $payload = $this->payload;
        $data = $payload->all();

        $validator = $this->validator($data, [
            'branch_id' => 'required',
            'deliveries' => 'required',
        ]);

        if ($validator->fails()) {
            return Rest::validationFailed($validator);
        }

        $branchId = $payload->branch_id;
        $deliveries = $payload->deliveries;
        $remarks = "";
        $invoiceNo = "";

        $isFranchisee = $this->companyService->isBranchFranchisee($branchId);

        if (empty($deliveries)) {
            return Rest::failed("Please input at least 1 product and quantity.");
        }

        if (isset($data['remarks'])) {
            $remarks = $data['remarks'];
        }

        if (isset($data['invoice_no'])) {
            $invoiceNo = $data['invoice_no'];
        }

        if ($isFranchisee && ($invoiceNo == "" || $invoiceNo == null)) {
            return Rest::failed("Cannot deliver items. Please input valid invoice number.");
        }

        $deliveryValidations = $this->productService->validateStocksDelivery($deliveries);
        $hasEnoughStocks = $this->productService->hasEnoughStocksByValidation($deliveryValidations);

        if (!$hasEnoughStocks) {
            return Rest::failed("Not enough Stocks", $deliveryValidations);
        }

        $deliveryDate = Carbon::now()->toDateTimeString();

        $delivery = $this->productService->createDelivery($branchId, StatusHelper::CONFIRMED, $remarks, $deliveryDate, $invoiceNo);

        if (!$delivery) {
            return Rest::failed("Something went wrong while creating delivery");
        }

        $deliveryId = $delivery->id;
        $deliveryItems = $this->productService->createDeliveryItems($deliveryId, $deliveries);

        $deliveriesWithStatus = $this->productService->subtractStocksByDeliveries($deliveries);
        $subtracted = $this->productService->hasEnoughStocksByValidation($deliveriesWithStatus);

        if (!$subtracted) {
            return Rest::failed("Something went wrong while subtracting stocks", $deliveriesWithStatus);
        }

        $branchDeliveriesWithStatus = $this->productService->addBranchStocks($branchId, $deliveries);
        $added = $this->productService->hasEnoughStocksByValidation($branchDeliveriesWithStatus);

        if (!$added) {
            return Rest::failed("Something went wrong while adding stocks to branch", $branchDeliveriesWithStatus);
        }

        $this->productService->updateBranchStocksCurrentDeliveryQuantity($branchId, $deliveries);

        $userId = $this->loggedInUserId;
        $transaction = $this->transactionService->deliverStocksToBranches($branchId, $deliveries, $userId, $remarks);

        if (!$transaction) {
            return Rest::failed("Something went wrong while creating transaction", $transaction);
        }

        // check if branch is a franchisee - if so, add delivery sale transaction
        if ($isFranchisee) {
            $deliverySaleTransaction = $this->transactionService->createDeliverySaleTransaction($branchId, $deliveries, $userId, $invoiceNo, $remarks);
        }

        if (!isset($deliverySaleTransaction['transaction']) && $isFranchisee) {
            return Rest::failed("Something went wrong while creating delivery sale transaction", $deliverySaleTransaction);
        }

        return Rest::success($delivery, [
            'items' => $deliveryItems
        ]);
    }

    public function updatePendingDeliveries($deliveryId)
    {
        $payload = $this->payload;
        $data = $payload->all();

        $validator = $this->validator($data, [
            'branch_id' => 'required',
            'deliveries' => 'required',
        ]);

        if ($validator->fails()) {
            return Rest::validationFailed($validator);
        }

        $branchId = $payload->branch_id;
        $deliveries = $payload->deliveries;

        if (empty($deliveries)) {
            return Rest::failed("Please input at least 1 product and quantity.");
        }

        return $this->updatePendingDeliveriesByBranchId($deliveryId, $branchId, $deliveries);

        // TODO: create activity log
    }

    public function returnBranchStocksToCompany()
    {

        $payload = $this->payload;
        $data = $payload->all();

        $validator = $this->validator($data, [
            'branch_id' => 'required',
            'deliveries' => 'required',
        ]);

        if ($validator->fails()) {
            return Rest::validationFailed($validator);
        }

        $branchId = $payload->branch_id;
        $deliveries = $payload->deliveries;
        $remarks = "";
        $invoiceNo = "";

        $userId = $this->loggedInUserId;

        if (isset($data['invoice_no'])) {
            $invoiceNo = $data['invoice_no'];
        }

        if (empty($deliveries)) {
            return Rest::failed("Cannot return items. Please input at least 1 product and quantity.");
        }

        $deliveryValidations = $this->productService->validateEnoughBranchStocks($branchId, $deliveries);
        $hasEnoughStocks = $this->productService->hasEnoughStocksByValidation($deliveryValidations);

        if (!$hasEnoughStocks) {
            return Rest::failed("Cannot return items. Not enough stocks to return.", $deliveryValidations);
        }

        $deliveryAtLeastOneZeroValue = $this->productService->hasAtLeastOneZeroValueStocksValidation($deliveries);

        if (!$deliveryAtLeastOneZeroValue) {
            return Rest::failed("Cannot return items. Please provide quantity for at least 1 item.");
        }

        if (isset($data['remarks'])) {
            $remarks = $data['remarks'];
        }

        return $this->returnBranchStocksByBranchId($branchId, $deliveries, $remarks, $invoiceNo);

        // TODO: create activity log


        // TODO: check if branch stocks for each product variation can be returned (IF NOT ERROR)

        // TODO: foreach product variation to return add company stocks
        // TODO: foreach product variation to return subtract branch stocks by branch id

        // TODO: create delivery with delivery items baseed on product variations
        // TODO: create transaction delivery with transaction items

        // TODO: create activity log
    }

    public function confirmPendingDeliveriesById($deliveryId)
    {
        $delivery = $this->productService->findDelivery($deliveryId);

        if (!$delivery) {
            return Rest::notFound("Delivery not found.");
        }

        $remarks = $delivery->remarks;
        $status = $delivery->status;
        $branchId = $delivery->branch_id;
        $invoiceNo = $delivery->invoice_no;

        $deliveryConfirmedStatus = StatusHelper::CONFIRMED;
        $deliveryDisabledStatus = StatusHelper::DISABLED;
        $deliveryPendingStatus = StatusHelper::PENDING;

        if ($status == $deliveryConfirmedStatus) {
            return Rest::failed("Delivery already confirmed.");
        }

        if ($status == $deliveryDisabledStatus) {
            return Rest::failed("Delivery is disabled.");
        }

        if ($status != $deliveryPendingStatus) {
            return Rest::failed("Delivery status must be pending in order to proceed.");
        }

        $deliveries = $this->productService->getDeliveryItemsByDeliveryId($deliveryId);

        $deliveryValidations = $this->productService->validateStocksDelivery($deliveries);
        $hasEnoughStocks = $this->productService->hasEnoughStocksByValidation($deliveryValidations);

        if (!$hasEnoughStocks) {
            return Rest::failed("Not enough Stocks", [
                'delivery_with_validations' => $deliveryValidations,
                'deliveries' => $deliveries
            ]);
        }

        $deliveriesWithStatus = $this->productService->subtractStocksByDeliveries($deliveries);
        $subtracted = $this->productService->hasEnoughStocksByValidation($deliveriesWithStatus);

        if (!$subtracted) {
            return Rest::failed("Something went wrong while subtracting stocks", $deliveriesWithStatus);
        }

        $branchDeliveriesWithStatus = $this->productService->addBranchStocks($branchId, $deliveries);
        $added = $this->productService->hasEnoughStocksByValidation($branchDeliveriesWithStatus);

        if (!$added) {
            return Rest::failed("Something went wrong while adding stocks to branch", $branchDeliveriesWithStatus);
        }

        $this->productService->updateBranchStocksCurrentDeliveryQuantity($branchId, $deliveries);

        $userId = $this->loggedInUserId;

        $deliveryStocksTransaction = $this->transactionService->deliverStocksToBranches($branchId, $deliveries, $userId, $remarks);

        if (!$deliveryStocksTransaction) {
            return Rest::failed("Something went wrong while creating transaction", $deliveryStocksTransaction);
        }

        //$subtractWarehouseStocksTransaction = $this->transactionService->subtractStocksByDeliveries

        // check if branch is a franchisee - if so, add delivery sale transaction
        $isFranchisee = $this->companyService->isBranchFranchisee($branchId);

        if ($isFranchisee) {
            $deliverySaleTransaction = $this->transactionService->createDeliverySaleTransaction($branchId, $deliveries, $userId, $invoiceNo, $remarks);
        }

        if (!isset($deliverySaleTransaction['transaction']) && $isFranchisee) {
            return Rest::failed("Something went wrong while creating delivery sale transaction", $deliverySaleTransaction);
        }

        $this->productService->updateDeliveryStatus($deliveryId, $deliveryConfirmedStatus);

        //$this->activityService->logConfirmPendingDelivery($this->loggedInUserId);

        return Rest::success($deliveries);
    }

    private function createPendingDeliveriesByBranchId($branchId, $deliveries, $remarks = "", $invoiceNo = null)
    {
        foreach ($deliveries as $delivery) {

            if (!isset($delivery['product_variation_id'])) {
                return Rest::failed("Product variation is required.");
            }

            if (!isset($delivery['quantity'])) {
                return Rest::failed("Quantity is required.");
            }
        }

        $date = Carbon::now()->toDateTimeString();

        $delivery = $this->productService->createDelivery($branchId, StatusHelper::PENDING, $remarks, $date, $invoiceNo);

        if (!$delivery) {
            return Rest::failed("Something went wrong while creating delivery");
        }

        // TODO: when product variation id does not exist - must validate

        $deliveryId = $delivery->id;
        $deliveryItems = $this->productService->createDeliveryItems($deliveryId, $deliveries);
        $updated = $this->productService->hasEnoughStocksByValidation($deliveryItems);

        if (!$updated) {
            $this->productService->deleteDelivery($deliveryId);
            return Rest::failed("Something went wrong while adding delivery items", $deliveryItems);
        }

        // TODO: create delivery pending delivery transaction
        //$this->activityService->logAddPendingDelivery($this->loggedInUserId);

        return Rest::success($delivery, [
            'items' => $deliveryItems
        ]);
    }

    private function updatePendingDeliveriesByBranchId($deliveryId, $branchId, $deliveries)
    {
        foreach ($deliveries as $delivery) {

            if (!isset($delivery['product_variation_id'])) {
                return Rest::failed("Product variation is required.");
            }

            if (!isset($delivery['quantity'])) {
                return Rest::failed("Quantity is required.");
            }
        }

        $deliveryUpdated = $this->productService->updateDelivery($deliveryId, $branchId);

        if (!$deliveryUpdated) {
            return Rest::failed("Something went wrong while updating delivery");
        }

        //remove delivery items and create new ones
        $deleted = $this->productService->deleteDeliveryItemsByDeliveryId($deliveryId);

        if (!$deleted) {
            return Rest::failed("Something went wrong while deleting delivery items");
        }

        // TODO: when product variation id does not exist - must validate

        $deliveryItems = $this->productService->createDeliveryItems($deliveryId, $deliveries);
        $updated = $this->productService->hasEnoughStocksByValidation($deliveryItems);

        if (!$updated) {
            $this->productService->deleteDelivery($deliveryId);
            return Rest::failed("Something went wrong while adding delivery items", $deliveryItems);
        }

        // TODO: update delivery pending delivery transaction

        return Rest::updateSuccess($updated, [
            'items' => $deliveryItems
        ]);
    }

    private function returnBranchStocksByBranchId($branchId, $deliveries, $remarks = "", $invoiceNo = null)
    {
        $userId = $this->loggedInUserId;

        foreach ($deliveries as $delivery) {

            if (!isset($delivery['product_variation_id'])) {
                return Rest::failed("Product variation is required.");
            }

            if (!isset($delivery['quantity'])) {
                return Rest::failed("Quantity is required.");
            }
        }

        $isFranchisee = $this->companyService->isBranchFranchisee($branchId);

        if ($isFranchisee && ($invoiceNo == "" || $invoiceNo == null)) {
            return Rest::failed("Cannot return items. Please input valid invoice number.");
        }

        // select from all transactions if there is an existing invoice no with sale as its transaction type
        $hasExistingSaleByInvoiceNo = $this->transactionService->hasExistingSaleByInvoiceNo($invoiceNo);

        if(!$hasExistingSaleByInvoiceNo && $isFranchisee){
            return Rest::failed("Cannot return items. Please provide invoice number for an existing delivery.");
        }

        // TODO: check if the total quantity to return is equivalent to (delivery total - sum of all previous return sale transactions)
        $deliveriesToReturn = $this->transactionService->hasEnoughReturnQuantitiesByInvoiceNo($invoiceNo, $deliveries);
        $hasEnoughReturnQuantity = $this->productService->hasEnoughStocksByValidation($deliveriesToReturn);

        if(!$hasEnoughReturnQuantity && $isFranchisee){
            return Rest::failed("Cannot return items. Not enough stocks available to return based from Invoice No: " . $invoiceNo);
        }

        $deliveryDate = Carbon::now()->toDateTimeString();

        $delivery = $this->productService->createDelivery($branchId, StatusHelper::RETURN, $remarks, $deliveryDate, $invoiceNo);

        if (!$delivery) {
            return Rest::failed("Something went wrong while returning delivery.");
        }

        $deliveryId = $delivery->id;
        $deliveryItems = $this->productService->createDeliveryItems($deliveryId, $deliveries);
        $updated = $this->productService->hasEnoughStocksByValidation($deliveryItems);

        if (!$updated) {
            $this->productService->deleteDelivery($deliveryId);
            return Rest::failed("Something went wrong while adding delivery items", $deliveryItems);
        }

        // return branch stocks to company
        $branchStockReturns = $this->productService->subtractBranchStocks($branchId, $deliveries);
        $companyAddedStocks = $this->productService->addCompanyStocksByVariations($deliveries);

        $returnTransaction = $this->transactionService->returnStocksToCompany($branchId, $deliveryItems, $userId, $remarks);

        if ($isFranchisee) {
            $returnDeliveryReturnSaleTransaction = $this->transactionService->createReturnDeliveryReturnSaleTransaction($branchId, $deliveries, $userId, $invoiceNo, $remarks);
        }

        if (!isset($returnDeliveryReturnSaleTransaction['transaction']) && $isFranchisee) {
            return Rest::failed("Cannot return items. Something went wrong while creating return transaction.");
        }

        // TODO: create delivery return delivery transaction
        //$this->activityService->logReturnStock($this->loggedInUserId, $transactionId);

        return Rest::success($delivery, [
            'items' => $branchStockReturns
        ]);
    }

    public function export()
    {
        $data = $this->payload->all();

        $export = $this->exportService->export($data);

        if (!$export) {
            return Rest::failed("Data might not exist on the database. Please try again");
        }

        $path = url('uploads/exports/' . $export);

        return Rest::success($path);
    }

    public function getSpecialDiscounts()
    {
        $ruleType = StatusHelper::PRICE_RULE_SPECIAL;

        $priceRules = $this->productService->getPriceRulesByType($ruleType);

        return Rest::success($priceRules);
    }
}
