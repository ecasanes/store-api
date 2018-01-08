<?php namespace App\DTIStore\Repositories;

interface TransactionInterface
{
    public function create(array $data);

    public function find($id);

    public function update($id, $data);

    public function delete($id);

    public function destroy($id);

    public function isDeleted($id);

    public function findByTrackingNo($trackingNo);

}