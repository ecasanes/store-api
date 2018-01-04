<?php namespace App\Mercury\Repositories;

interface BranchStaffInterface
{
    public function create(array $data);

    public function find($id);

    public function findByStaffId($staffId);

    public function getAll();

    public function filter(array $filter);

    public function getFilterMeta($data);

    public function update($id, $data);

    public function updateByUserId($id, $data);

    public function delete($id);

    public function destroy($id);

    public function isDeleted($id);

    public function findUserByKeyAndStaffId($key, $staffId);

    public function generateStaffId($userId, $branchId);

    public function findUserByStaffId($staffId);

    public function updateByStaffId($staffId, $data);

    public function findByUserId($userId);
}