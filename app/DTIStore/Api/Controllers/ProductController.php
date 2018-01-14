<?php

namespace App\DTIStore\Api\Controllers;

use App\DTIStore\Helpers\Rest;
use App\DTIStore\Helpers\StatusHelper;
use App\DTIStore\Services\ProductService;
use App\DTIStore\Services\OrderService;
use App\DTIStore\Services\UserService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    protected $productService;
    protected $orderService;
    protected $userService;

    public function __construct(
        Request $request,
        ProductService $productService,
        OrderService $orderService,
        UserService $userService
    )
    {
        parent::__construct($request);

        $this->productService = $productService;
        $this->orderService = $orderService;
        $this->userService = $userService;

    }

    public function getAll()
    {
        $payload = $this->payload;
        $data = $payload->all();

        $productsMeta = $this->productService->getFilterMeta($data);
        $products = $this->productService->filter($data);

        return Rest::success($products, $productsMeta);
    }

    public function getStoreStocksById($branchId)
    {
        $payload = $this->payload;
        $filter = $payload->all();

        $branchStocks = $this->productService->getBranchStocksById($branchId, $filter);

        return Rest::success($branchStocks);
    }

    public function create()
    {
        $payload = $this->payload;
        $data = $payload->all();

        $validator = $this->validator($data, [
            'name' => 'required|unique:products',
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

        $user = $this->user;

        if($user){

            $storeId = $this->userService->findStoreIdByUser($user->id);

            foreach($variations as $variation){
                $variationId = $variation['id'];
                $this->productService->addStoreStock($storeId, $variationId, 1);
            }

        }

        $product = $this->productService->find($productId);

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

        return Rest::updateSuccess($updated, $product);
    }

    public function delete($id)
    {
        $deleted = $this->productService->delete($id);

        return Rest::deleteSuccess($deleted);
    }

    public function addStoreStocksById($storeId)
    {
        $payload = $this->payload;
        $data = $payload->all();

        $validator = $this->validator($data, [
            'product_variation_id' => 'required',
            'quantity' => 'required'
        ]);

        if ($validator->fails()) {
            return Rest::validationFailed($validator);
        }

        $productVariationId = $data['product_variation_id'];
        $quantity = $data['quantity'];

        $addedStoreStock = $this->productService->addStoreStock($storeId, $productVariationId, $quantity);

        return Rest::success($addedStoreStock);
    }

    public function addToCart($userId)
    {
        $payload = $this->payload;
        $data = $payload->all();

        $validator = $this->validator($data, [
            'product_variation_id' => 'required',
            'cart_quantity' => 'required'
        ]);

        if ($validator->fails()) {
            return Rest::validationFailed($validator);
        }

        $productVariationId = $data['product_variation_id'];
        $quantity = $data['cart_quantity'];

        $added = $this->productService->addToCart($productVariationId, $userId, $quantity);

        return Rest::success($added);
    }

    public function removeInCart()
    {
        $payload = $this->payload;
        $data = $payload->all();

        $validator = $this->validator($data, [
            'product_variation_id' => 'required',
            'user_id' => 'required'
        ]);

        if ($validator->fails()) {
            return Rest::validationFailed($validator);
        }

        $productVariationId = $data['product_variation_id'];
        $userId = $data['user_id'];

        $deleted = $this->productService->removeInCart($productVariationId, $userId);

        return Rest::success($deleted);
    }

    public function addToWishlist()
    {
        $payload = $this->payload;
        $data = $payload->all();

        $validator = $this->validator($data, [
            'product_variation_id' => 'required',
            'user_id' => 'required'
        ]);

        if ($validator->fails()) {
            return Rest::validationFailed($validator);
        }

        $productVariationId = $data['product_variation_id'];
        $userId = $data['user_id'];

        $added = $this->productService->addToWishlist($productVariationId, $userId);

        return Rest::success($added);
    }

    public function removeInWishlist()
    {
        $payload = $this->payload;
        $data = $payload->all();

        $validator = $this->validator($data, [
            'product_variation_id' => 'required',
            'user_id' => 'required'
        ]);

        if ($validator->fails()) {
            return Rest::validationFailed($validator);
        }

        $productVariationId = $data['product_variation_id'];
        $userId = $data['user_id'];

        $deleted = $this->productService->removeInWishlist($productVariationId, $userId);

        return Rest::success($deleted);
    }

    public function getCartCountByUser($userId)
    {
        $count = $this->productService->getCartCountByUser($userId);

        return Rest::success($count);
    }

    public function getAllPaymentMethods()
    {
        $paymentMethods = $this->productService->getAllPaymentMethods();

        return Rest::success($paymentMethods);
    }

    public function getAllVouchers()
    {
        $vouchers = $this->productService->getAllVouchers();

        return Rest::success($vouchers);
    }

    public function createVoucher()
    {
        $payload = $this->payload;
        $data = $payload->all();

        $validator = $this->validator($data, [
            'name' => 'required',
            'code' => 'required|unique:vouchers',
            'discount_type' => 'required',
            'discount' => 'required'
        ]);

        if ($validator->fails()) {
            return Rest::validationFailed($validator);
        }

        $voucher = $this->productService->createVoucher($data);

        if(!$voucher){
            return Rest::failed("Something went wrong while creating voucher");
        }

        return Rest::success($voucher);
    }

    public function updateVoucher($id)
    {
        $payload = $this->payload;
        $data = $payload->all();

        $validator = $this->validator($data, [
            'name' => 'required',
            'code' => 'unique:vouchers,code,' . $id,
            'discount_type' => 'required',
            'discount' => 'required'
        ]);

        if ($validator->fails()) {
            return Rest::validationFailed($validator);
        }

        $updated = $this->productService->updateVoucher($id, $data);

        if(!$updated){
            return Rest::failed("Something went wrong while updating voucher");
        }

        return Rest::success($updated);

    }

    public function deleteVoucher($id)
    {
        $deleted =  $this->productService->deleteVoucher($id);

        return Rest::success($deleted);
    }

    public function validateVoucherByCode()
    {
        $payload = $this->payload;
        $data = $payload->all();

        $validator = $this->validator($data, [
            'code' => 'required'
        ]);

        if ($validator->fails()) {
            return Rest::validationFailed($validator);
        }

        $voucherCode = $data['code'];

        $voucher = $this->productService->validateVoucherByCode($voucherCode);

        return Rest::success($voucher);

    }

    public function getAllCategories()
    {
        $payload = $this->payload;
        $filter = $payload->all();

        $byProducts = false;

        if(isset($filter['with_products'])){
            $byProducts = $filter['with_products'];
        }

        if($byProducts){

            $categories = $this->productService->getAllCategoriesWithProducts();

            return Rest::success($categories);

        }

        $categories = $this->productService->getAllProductCategories();

        return Rest::success($categories);
    }

    public function getAllConditions()
    {
        $conditions = $this->productService->getAllConditions();

        return Rest::success($conditions);
    }


}
