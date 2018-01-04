<?php

namespace App\Mercury\Api\Controllers;

use App\Mercury\Helpers\Rest;
use App\Mercury\Services\ProductService;
use App\Mercury\Services\ExportService;
use Illuminate\Http\Request;


class ProductCategoryController extends Controller
{
    protected $productService;
    protected $exportService;

    public function __construct(Request $request, ProductService $productService, ExportService $exportService)
    {
        parent::__construct($request);
        $this->productService = $productService;
        $this->payload = $request;
        $this->exportService = $exportService;
    }

    public function getAll()
    {
        $data = $this->payload->all();

//        $categories = $this->productService->getAllProductCategories();
        $categories = $this->productService->filterProductCategories($data);

        $categoriesMeta = $this->productService->getProductCategoryMeta($data);

        return Rest::success($categories, $categoriesMeta);
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

}