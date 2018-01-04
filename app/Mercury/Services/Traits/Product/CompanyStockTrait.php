<?php namespace App\Mercury\Services\Traits\Product;

trait CompanyStockTrait{

    public function addStocksByProductId($productId, $quantity)
    {

        $added = $this->companyStock->addStocksByProductId($productId, $quantity);

        return $added;
    }

    public function subtractStocksByProductId($productId, $quantity)
    {

        $subtracted = $this->companyStock->subtractStocksByProductId($productId, $quantity);

        return $subtracted;
    }

    public function addStocksByVariationId($variationId, $quantity)
    {
        $added = $this->companyStock->addStocksByVariationId($variationId, $quantity);

        return $added;
    }

    public function subtractStocksByVariationId($variationId, $quantity)
    {
        $added = $this->companyStock->subtractStocksByVariationId($variationId, $quantity);

        return $added;
    }

    public function isVariationHasEnoughStocks($variationId, $quantity)
    {
        $companyStock = $this->companyStock->findByVariationId($variationId);

        if(!$companyStock){
            return false;
        }

        $currentQuantity = $companyStock->quantity;

        if($currentQuantity < $quantity){
            return false;
        }

        return true;
    }

    public function addCompanyStocksByVariations(array $variations = []){

        $companyStocks = [];

        foreach($variations as $variation){

            $valid = true;

            if(isset($variation['valid'])){
                $valid = $variation['valid'];
            }

            if(!$valid){
                continue;
            }

            $productVariationId = null;

            if(isset($variation['id'])){
                $productVariationId = $variation['id'];
            }

            if(isset($variation['product_variation_id'])){
                $productVariationId = $variation['product_variation_id'];
            }

            $quantity = $variation['quantity'];

            $companyStocks[] = $this->addStocksByVariationId($productVariationId, $quantity);
        }

        return $companyStocks;

    }

    public function getCompanyStocks($filter)
    {
        $companyStocks = $this->companyStock->filter($filter);

        return $companyStocks;
    }

}