<?php namespace App\DTIStore\Repositories;

use App\CompanyStock;
use App\DTIStore\Helpers\StatusHelper;
use App\Product;
use App\ProductVariation as Variation;
use App\ProductVariation;
use Carbon\Carbon;
use DB;

class ProductVariationRepository implements ProductVariationInterface
{

    public function create(array $data)
    {
        // TODO: refactor create method in the future maybe (?)

        unset($data['token']);
        unset($data['product_variation_id']);

        $variation = Variation::firstOrCreate($data);

        return $variation;
    }

    public function find($id)
    {
        $variation = Variation::where('product_variations.id', $id)
            ->join('products', 'products.id', '=', 'product_variations.product_id')
            ->select(
                'product_variations.*',
                'products.name',
                'products.code',
                'products.image_url',
                'products.product_category_id',
                'products.description'
            )->first();

        return $variation;
    }

    public function findWithCompanyStocks($id)
    {
        $variation = Variation::where('product_variations.id', $id)
            ->join('products', 'products.id', '=', 'product_variations.product_id')
            ->leftJoin('company_stocks','company_stocks.product_variation_id','=','product_variations.id')
            ->select(
                'product_variations.*',
                'products.name',
                'products.code',
                'products.image_url',
                'products.product_category_id',
                'products.description',
                'company_stocks.quantity as company_quantity',
                DB::raw("(SELECT SUM(branch_stocks.`quantity`) FROM branch_stocks  WHERE branch_stocks.product_variation_id = product_variations.id) as total_branch_quantity"),
                DB::raw("(SELECT SUM(branch_stocks.`quantity`) FROM branch_stocks  WHERE branch_stocks.product_variation_id = product_variations.id) + company_stocks.quantity as total_quantity")
            )->first();

        return $variation;
    }

    public function getAll()
    {
        $activeFlag = StatusHelper::ACTIVE;

        $sql = "SELECT 
                  product_variations.`id`,
                  product_variations.size,
                  product_variations.metrics,
                  product_variations.cost_price,
                  product_variations.selling_price,
                  product_variations.franchisee_price,
                  product_variations.status,
                  product_variations.product_id,
                  company_stocks.quantity 
                FROM
                  product_variations 
                  INNER JOIN company_stocks 
                    ON company_stocks.`product_variation_id` = product_variations.`id` 
                WHERE product_variations.`status` = '{$activeFlag}' ";

        $variations = DB::select($sql);

        return $variations;
    }

    public function getAllByProductId($id)
    {
        $activeFlag = StatusHelper::ACTIVE;

        $sql = "SELECT 
                  product_variations.`id`,
                  product_variations.size,
                  product_variations.metrics,
                  product_variations.cost_price,
                  product_variations.selling_price,
                  product_variations.franchisee_price,
                  product_variations.status,
                  product_variations.product_id,
                  company_stocks.quantity 
                FROM
                  product_variations 
                  INNER JOIN company_stocks 
                    ON company_stocks.`product_variation_id` = product_variations.`id` 
                WHERE product_variations.`status` = '{$activeFlag}' AND product_variations.product_id = {$id} ";

        $variations = DB::select($sql);

        return $variations;
    }

    public function filter(array $filter)
    {
        // TODO: filters
        $variations = $this->getAll();

        return $variations;
    }

    public function update($id, $data)
    {
        $variation = ProductVariation::find($id);

        if (!$variation) {
            return false;
        }

        $updated = $variation->update($data);

        return $updated;
    }

    public function updateWithProductData($id, $data)
    {
        return DB::transaction(function () use ($id, $data) {

            $variation = ProductVariation::find($id);

            $variation->update($data);

            $productId = $variation->product_id;

            $product = Product::find($productId);

            // except image
            unset($data['image_url']);
            $product->update($data);



            if(isset($data['company_quantity'])){

                $companyQuantity = $data['company_quantity'];

                $companyStock = CompanyStock::where('product_variation_id', $id)->first();

                $companyStock->update([
                    'quantity' => $companyQuantity
                ]);
            }



        });
    }

    public function delete($id)
    {
        $variation = ProductVariation::find($id);

        if (!$variation) {
            return false;
        }

        if ($variation->status == StatusHelper::DELETED) {
            return true;
        }

        $deleted = $variation->update([
            'deleted_at' => Carbon::now()->toDateTimeString(),
            'status' => StatusHelper::DELETED,
        ]);

        return $deleted;
    }

    public function destroy($id)
    {
        $variation = ProductVariation::find($id);

        if (!$variation) {
            return false;
        }

        $destroyed = $variation->delete();

        return $destroyed;
    }

    public function isDeleted($id)
    {
        $variation = ProductVariation::find($id);

        if (!$variation) {
            return true;
        }

        if ($variation->status != StatusHelper::DELETED) {
            return false;
        }

        return true;
    }
}