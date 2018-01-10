<?php

namespace App\DTIStore\Api\Controllers;

use App\DTIStore\Helpers\Rest;
use App\DTIStore\Helpers\StatusHelper;
use App\DTIStore\Services\OrderService;
use App\DTIStore\Services\StoreService;
use App\DTIStore\Services\ProductService;
use App\DTIStore\Services\PusherService;
use App\DTIStore\Services\RoleService;
use App\DTIStore\Services\UserService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class OrderController extends Controller
{
    protected $productService;
    protected $storeService;
    protected $orderService;

    public function __construct(
        Request $request,
        ProductService $productService,
        StoreService $storeService,
        OrderService $orderService
    )
    {
        parent::__construct($request);

        $this->productService = $productService;
        $this->storeService = $storeService;
        $this->orderService = $orderService;
    }

    public function filter()
    {
        $payload = $this->payload;
        $filter = $payload->all();

        $orders = $this->productService->filter($filter);

        return Rest::success($orders);
    }

    public function create()
    {
        $payload = $this->payload;
        $data = $payload->all();

        $validator = $this->validator($data, [
            'buyer_user_id' => 'required',
            'payment_mode_id' => 'required',
            'address' => 'required',
            'products' => 'required'
        ]);

        if ($validator->fails()) {
            return Rest::validationFailed($validator);
        }

        $products = $data['products'];

        if(empty($products)){
            return Rest::failed("Please add at least 1 product in cart");
        }

        if(!is_array($products)){
            return Rest::failed("Product input not right.");
        }

        $paymentModeId = $data['payment_mode_id'];

        $voucherCode = null;
        if(isset($data['voucher'])){
            $voucherCode = $data['voucher'];
        }

        $voucher = $this->productService->validateVoucherByCode($voucherCode);

        $total = $this->productService->calculateTotalByProducts($products, $voucherCode);
        $discount = 0;

        if($voucher){

            $voucherId = $voucher->id;
            $voucherDiscoutType = $voucher->discount_type;
            $voucherDiscount = $voucher->discount;

            $data['voucher_id'] = $voucherId;
            $data['voucher_code'] = $voucherCode;
            $data['discount_type'] = $voucherDiscoutType;
            $data['discount'] = $voucherDiscount;

            if($voucherDiscoutType == 'percent'){
                $percentOff = $total*$voucherDiscount;
                $total = $total - $percentOff;
                $discount = $percentOff;
            }

            if($voucherDiscoutType == 'fixed'){
                $total = $total - $voucherDiscount;
                $discount = $voucherDiscount;
            }
        }

        $data['total'] = $total;

        $order = $this->orderService->create($data);

        if(!$order){
            return Rest::failed("Something went wrong while adding new order");
        }

        $orderId = $order->id;

        $createdRows = $this->orderService->createTransactionsByProducts($orderId, $products, $paymentModeId, $discount);

        $buyerUserId = $data['buyer_user_id'];

        $this->productService->subtractStoreStocksByProducts($products);
        $this->productService->removeInCartByProducts($buyerUserId, $products);

        return Rest::success([
            'order' => $order
        ] + $createdRows);


    }

    public function update()
    {

    }

    public function delete()
    {

    }

    public function createTransaction()
    {

    }

    public function updateTransaction()
    {

    }

    public function deleteTransaction()
    {

    }

    public function createTransactionItem()
    {

    }

    public function updateTransactionItem()
    {

    }

    public function deleteTransactionItem()
    {

    }

    public function getAllOrderHistory()
    {
        $payload = $this->payload;
        $filter = $payload->all();

        $orderHistory = $this->orderService->getOrderTransactions($filter);

        return Rest::success($orderHistory);
    }

    public function getCurrentBuyerOrderHistory()
    {
        $payload = $this->payload;
        $filter = $payload->all();

        $userId = $this->user->id;
        $filter['buyer_user_id'] = $userId;

        $orderHistory = $this->orderService->getOrderTransactions($filter);

        return Rest::success($orderHistory);
    }

    public function getCurrentSellerOrderHistory()
    {
        $payload = $this->payload;
        $filter = $payload->all();

        $userId = $this->user->id;
        $filter['seller_user_id'] = $userId;

        $orderHistory = $this->orderService->getOrderTransactions($filter);

        return Rest::success($orderHistory);
    }

    public function receiveTransactionById($transactionId)
    {
        $transaction = $this->orderService->findTransaction($transactionId);

        if(!$transaction){
            return Rest::notFound("Transaction not found");
        }

        $updated = $this->orderService->updateTransaction($transactionId, [
            'buyer_status' => 'received',
            'seller_status' => 'delivered'
        ]);

        if(!$updated){
            return Rest::failed("Something went wrong while updating transaction");
        }

        return Rest::success($updated);

    }

    public function shipTransactionById($transactionId)
    {
        $transaction = $this->orderService->findTransaction($transactionId);

        if(!$transaction){
            return Rest::notFound("Transaction not found");
        }

        $updated = $this->orderService->updateTransaction($transactionId, [
            'tracking_no' => $this->orderService->generateTrackingNo(),
            'seller_status' => 'shipped'
        ]);

        if(!$updated){
            return Rest::failed("Something went wrong while updating transaction");
        }

        return Rest::success($updated);

    }

    public function receivePaymentTransactionById($transactionId)
    {
        $transaction = $this->orderService->findTransaction($transactionId);

        if(!$transaction){
            return Rest::notFound("Transaction not found");
        }

        $updated = $this->orderService->updateTransaction($transactionId, [
            'seller_status' => 'received'
        ]);

        if(!$updated){
            return Rest::failed("Something went wrong while updating transaction");
        }

        return Rest::success($updated);

    }

    public function completeTransactionById($transactionId)
    {
        $transaction = $this->orderService->findTransaction($transactionId);

        if(!$transaction){
            return Rest::notFound("Transaction not found");
        }

        $updated = $this->orderService->updateTransaction($transactionId, [
            'buyer_status' => 'completed',
            'seller_status' => 'completed'
        ]);

        if(!$updated){
            return Rest::failed("Something went wrong while updating transaction");
        }

        return Rest::success($updated);

    }



}
