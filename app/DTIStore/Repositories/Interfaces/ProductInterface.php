<?php namespace App\DTIStore\Repositories;

interface ProductInterface
{
    public function create(array $data);

    public function find($id);

    public function getAll();

    public function filter(array $filter);

    public function update($id, $data);

    public function delete($id);

    public function destroy($id);

    public function isDeleted($id);

    public function getCountAll();

    public function getCountByFilter(array $filter);

    public function getFilterMeta($data);

    public function getByTransactionId($transactionId, array $filter);

    public function getByTransactionIdMeta($transactionId, $data);
}