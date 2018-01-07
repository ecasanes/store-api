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

        $total = $this->productService->calculateTotalByProducts($products);
        $data['total'] = $total;

        $order = $this->orderService->create($data);

        if(!$order){
            return Rest::failed("Something went wrong while adding new order");
        }

        $orderId = $order->id;

        $createdRows = $this->orderService->createTransactionsByProducts($orderId, $products, $paymentModeId);

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

    public function getAllBuyersOrderHistory()
    {
        $payload = $this->payload;
        $filter = $payload->all();

        $userId = $this->user->id;
        $filter['buyer_user_id'] = $userId;

        $orderHistory = $this->orderService->getAllBuyersOrderHistory($filter);

        return Rest::success($orderHistory);
    }



}
