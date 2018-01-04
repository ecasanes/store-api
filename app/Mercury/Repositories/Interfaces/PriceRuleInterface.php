<?php namespace App\Mercury\Repositories;

interface PriceRuleInterface
{
    public function getAll();

    public function find($id);

    public function findByCode($code);

    public function filter(array $filter);

    public function getFilterMeta($data);

    public function create(array $data);

    public function update($id, $data);

    public function delete($id);

    public function destroy($id);

    public function isDeleted($id);

    public function getPriceRulesByApplication($guest);

    public function getPriceRulesByType($ruleType);
}