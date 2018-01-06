<?php namespace App\DTIStore\Services\Traits\Product;

trait ProductVariationTrait
{

    public function createProductVariations($productId, array $variations)
    {
        $productVariations = [];

        foreach($variations as &$variation){

            unset($variation['name']);
            unset($variation['isSelected']);
            unset($variation['subtotal']);
            unset($variation['returned_quantity']);

            $variation['product_id'] = $productId;

            $createdVariation = $this->variation->create($variation);

            if(!$createdVariation){
                $variation['valid'] = false;
            }

            if($createdVariation){
                $variation['valid'] = true;
                $variation['id'] = $createdVariation->id;
            }

            $productVariations[] = $variation;

        }


        return $productVariations;
    }

    public function createProductVariation($productId, array $data)
    {

        $data['product_id'] = $productId;
        $variation = $this->variation->create($data);

        return $variation;
    }

    public function findProductVariation($id)
    {
        $variation = $this->variation->find($id);

        return $variation;
    }

    public function findProductVariationWithCompanyStocks($id)
    {
        $variation = $this->variation->findWithCompanyStocks($id);

        return $variation;
    }

    public function getAllProductVariations($id)
    {
        $variations = $this->variation->getAllByProductId($id);

        return $variations;
    }

    public function updateProductVariation($id, $data)
    {
        $updated = $this->variation->update($id, $data);

        return $updated;
    }

    public function deleteProductVariation($id)
    {
        $deleted = $this->variation->delete($id);

        return $deleted;
    }

    public function productVariationIsDeleted($id)
    {
        $isDeleted = $this->variation->isDeleted($id);

        return $isDeleted;
    }

    public function getProductVariationsByProductId($productId)
    {
        $productVariations = $this->variation->getAllByProductId($productId);

        return $productVariations;
    }

}