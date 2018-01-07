<?php

namespace App\DTIStore\Api\Controllers;

use App\DTIStore\Helpers\Rest;
use App\DTIStore\Services\ProductService;
use Illuminate\Http\Request;


class ProductCategoryController extends Controller
{
    protected $productService;

    public function __construct(
        Request $request,
        ProductService $productService
    )
    {
        parent::__construct($request);
        $this->productService = $productService;
        $this->payload = $request;
    }

    public function getAll()
    {
        $data = $this->payload->all();

//        $categories = $this->productService->getAllProductCategories();
        $categories = $this->productService->filterProductCategories($data);

        $categoriesMeta = $this->productService->getProductCategoryMeta($data);

        return Rest::success($categories, $categoriesMeta);
    }

    public function getAllConditions()
    {

        $conditions = $this->productService->getAllConditions();

        return Rest::success($conditions);
    }

    public function get($id)
    {
        $category = $this->productService->findProductCategory($id);

        if(!$category){
            return Rest::notFound("Product category not found.");
        }

        return Rest::success($category);
    }

    public function create()
    {
        $payload = $this->payload;
        $data = $payload->all();

        $validator = $this->validator($data, [
            'name' => 'required|unique:product_categories',
            'code' => 'required|unique:product_categories',
        ]);

        if($validator->fails()) {
            return Rest::validationFailed($validator);
        }

        $category = $this->productService->createProductCategory($data);

        return Rest::success($category);

    }

    public function update($id)
    {
        $payload = $this->payload;
        $data = $payload->all();

        $validator = $this->validator($data, [
            'name' => 'required|unique:product_categories,name,'.$id,
            'code' => 'required|unique:product_categories,code,'.$id,
        ]);

        if ($validator->fails()) {
            return Rest::validationFailed($validator);
        }

        $isDeleted = $this->productService->productCategoryIsDeleted($id);

        if($isDeleted){
            return Rest::notFound("Category not found.");
        }

        $updated = $this->productService->updateProductCategory($id, $data);
        $product = $this->productService->findProductCategory($id);

        return Rest::updateSuccess($updated, $product);
    }

    public function delete($id)
    {
        $deleted = $this->productService->deleteProductCategory($id);

        return Rest::deleteSuccess($deleted);
    }

}