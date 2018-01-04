<?php namespace App\Mercury\Services\Traits\Product;

trait ProductCategoryTrait{

    public function getAllProductCategories()
    {
        $categories = $this->category->getAll();

        return $categories;
    }

    public function filterProductCategories(array $filter)
    {
        $categories = $this->category->filter($filter);

        return $categories;
    }

    public function getProductCategoryMeta($data)
    {
        $meta = $this->category->getFilterMeta($data);

        return $meta;
    }

    public function findProductCategory($id)
    {
        $category = $this->category->find($id);

        return $category;
    }

    public function createProductCategory(array $data)
    {
        $category = $this->category->create($data);

        return $category;
    }

    public function updateProductCategory($id, $data)
    {
        $updated = $this->category->update($id, $data);

        return $updated;
    }

    public function deleteProductCategory($id)
    {
        $deleted = $this->category->delete($id);

        return $deleted;
    }

    public function productCategoryIsDeleted($id)
    {
        $isDeleted = $this->category->isDeleted($id);

        return $isDeleted;
    }

    public function getProductCategoriesByTransactionId($transactionId)
    {
        $categories = $this->category->getByTransactionId($transactionId);

        return $categories;
    }

}