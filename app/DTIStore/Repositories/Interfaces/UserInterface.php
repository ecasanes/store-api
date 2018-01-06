<?php namespace App\DTIStore\Repositories;

interface UserInterface
{
    public function create(array $data);

    public function find($id);

    public function getAll();

    public function getCountAll();

    public function getCountByFilter(array $filter);

    public function filter(array $filter);

    public function update($id, $data);

    public function delete($id);

    public function destroy($id);

    public function isDeleted($id);

    public function getFilterMeta($data);

    public function getCompanyManagerPermissionByUserId($id);

    public function findByCustomerId($customerId);

    public function findByFirstnameAndLastname($firstname, $lastname);

    public function getFranchiseeByEmail($email);

}