<?php namespace App\Mercury\Services;

use App\Mercury\Repositories\PriceRuleInterface;

class PriceRuleService
{
    protected $pricing;

    public function __construct(PriceRuleInterface $pricing)
    {
        $this->pricing = $pricing;
    }

    public function getAll()
    {
        $pricingList = $this->pricing->getAll();

        return $pricingList;
    }

    public function get($id)
    {
        $pricing = $this->pricing->find($id);

        return $pricing;
    }

    public function findByCode($code)
    {
        $pricing = $this->pricing->findByCode($code);

        return $pricing;
    }

    public function filter($data)
    {
        $pricingList = $this->pricing->filter($data);

        return $pricingList;
    }

    public function getFilterMeta($data)
    {
        $meta = $this->pricing->getFilterMeta($data);

        return $meta;
    }

    public function create(array $data)
    {
        $pricing = $this->pricing->create($data);

        return $pricing;
    }

    public function update($id, $data)
    {
        $updated = $this->pricing->update($id, $data);

        return $updated;
    }

    public function delete($id)
    {
        $deleted = $this->pricing->delete($id);

        return $deleted;
    }

    public function isDeleted($id)
    {
        $isDeleted = $this->pricing->isDeleted($id);

        return $isDeleted;
    }

}