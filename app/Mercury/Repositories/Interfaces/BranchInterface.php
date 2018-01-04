<?php namespace App\Mercury\Repositories;

interface BranchInterface
{
    public function create(array $data);

    public function find($id);

    public function getAll();

    public function filter(array $filter);

    public function update($id, $data);

    public function delete($id);

    public function destroy($id);

    public function isDeleted($id);

    public function findByKey($key);

    public function getAllByCompanyId($companyId);

}