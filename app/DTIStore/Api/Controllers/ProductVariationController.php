<?php

namespace App\DTIStore\Api\Controllers;

use App\DTIStore\Helpers\Rest;
use App\DTIStore\Services\ProductService;
use Illuminate\Http\Request;

class ProductVariationController extends Controller
{
    protected $productService;

    public function __construct(Request $request, ProductService $productService)
    {
        parent::__construct($request);
        $this->productService = $productService;
    }

    public function getAll()
    {
        $variations = $this->productService->getAll();

        return Rest::success($variations);
    }

    public function get($id)
    {
        $variation = $this->productService->findProductVariationWithCompanyStocks($id);

        if(!$variation){
            return Rest::notFound("Product does not exist");
        }

        return Rest::success($variation);

    }

    public function getAllByProductId($id)
    {
        $variations = $this->productService->getAllProductVariations($id);

        return Rest::success($variations);
    }

    public function getByBranch()
    {
        return Rest::success($this->payload->all());
    }

    public function createByProductId($productId)
    {
        $payload = $this->payload;
        $data = $payload->all();

        $validator = $this->validator($data, [
            'size' => 'required',
            'metrics' => 'required',
            'cost_price' => 'required',
            'selling_price' => 'required',
        ]);

        if ($validator->fails()) {
            return Rest::validationFailed($validator);
        }

        $variation = $this->productService->createProductVariation($productId, $data);

        return Rest::success($variation);
    }

    public function create()
    {
        $payload = $this->payload;
        $data = $payload->all();

        $validator = $this->validator($data, [
            'size' => 'required',
            'metrics' => 'required',
            'cost_price' => 'required',
            'selling_price' => 'required',
            'product_id' => 'required'
        ]);

        if ($validator->fails()) {
            return Rest::validationFailed($validator);
        }

        $productId = $data['product_id'];
        $variation = $this->productService->createProductVariation($productId, $data);

        return Rest::success($variation);
    }


    public function update($id)
    {
        $payload = $this->payload;
        $data = $payload->all();

        $includeProduct = false;
        $includeProductRequiredData = [];

        $variation = $this->productService->findProductVariation($id);

        $baseRequiredData = [
            'size' => 'required',
            'metrics' => 'required',
            'cost_price' => 'required',
            'selling_price' => 'required'
        ];

        if (isset($data['include_product'])) {
            $includeProduct = $data['include_product'];
        }

        if ($includeProduct) {
            $includeProductRequiredData = [
                'name' => 'required',
                'code' => 'required',
                'product_category_id' => 'required',
                //'company_quantity' => 'required',
            ];
        }


        if (!$variation) {
            return Rest::notFound("Product Variation not found.");
        }

        $requiredData = $baseRequiredData + $includeProductRequiredData;

        $validator = $this->validator($data, $requiredData);

        if ($validator->fails()) {
            return Rest::validationFailed($validator);
        }

        $isDeleted = $this->productService->productVariationIsDeleted($id);

        if ($isDeleted) {
            return Rest::notFound("Product Variation not found.");
        }

        unset($data['company_quantity']);
        //return Rest::failed($data);
        $updated = $this->productService->updateProductVariationWithProductData($id, $data);
        $variation = $this->productService->findProductVariation($id);

        return Rest::updateSuccess($updated, $variation);
    }

    public function delete($id)
    {
        $deleted = $this->productService->deleteProductVariation($id);

        return Rest::deleteSuccess($deleted);
    }
}
