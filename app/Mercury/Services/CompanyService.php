<?php namespace App\Mercury\Services;

use App\Mercury\Helpers\StatusHelper;
use App\Mercury\Repositories\BranchInterface;
use App\Mercury\Repositories\BranchStaffInterface;
use App\Mercury\Repositories\CompanyInterface;
use App\Mercury\Repositories\UserInterface;
use App\Mercury\Repositories\UserRoleInterface;

class CompanyService
{
    protected $company;
    protected $branch;
    protected $branchStaff;
    protected $userRole;

    public function __construct(
        CompanyInterface $company,
        BranchInterface $branch,
        BranchStaffInterface $branchStaff,
        UserRoleInterface $userRole
    )
    {
        $this->company = $company;
        $this->branch = $branch;
        $this->branchStaff = $branchStaff;
        $this->userRole = $userRole;
    }

    public function generateBranchKey()
    {
        $key = null;
        $notUnique = true;

        while($notUnique){

            $key = rand(10000000, 99999999);

            $branch = $this->branch->findByKey($key);

            if($branch){
                continue;
            }

            $notUnique = false;

        }

        return $key;
    }

    public function createBranch(array $data)
    {
        $data['key'] = $this->generateBranchKey();
        $branch = $this->branch->create($data);

        return $branch;
    }

    public function findBranch($id)
    {
        $branch = $this->branch->find($id);

        return $branch;
    }

    public function getAllBranches($filter)
    {

        $branches = $this->branch->filter($filter);

        return $branches;

    }

    public function updateBranch($id, $data)
    {
        $updated = $this->branch->update($id, $data);

        return $updated;
    }

    public function deleteBranch($id)
    {
        $deleted = $this->branch->delete($id);

        return $deleted;
    }

    public function isBranchDeleted($id)
    {
        $isDeleted = $this->branch->isDeleted($id);

        return $isDeleted;
    }

    public function findUserByKeyAndStaffId($key, $staffId)
    {
        $user = $this->branchStaff->findUserByKeyAndStaffId($key, $staffId);

        return $user;
    }

    public function findUserByStaffId($staffId)
    {
        $user = $this->branchStaff->findUserByStaffId($staffId);

        return $user;
    }

    public function findBranchByKey($key)
    {
        $branch = $this->branch->findByKey($key);

        return $branch;
    }

    public function getAll()
    {
        $companies = $this->company->getAll();

        return $companies;
    }

    public function isDeleted($id)
    {
        $deleted = $this->company->isDeleted($id);

        return $deleted;
    }

    public function update($id, $data)
    {
        $updated = $this->company->update($id, $data);

        return $updated;
    }

    public function find($id)
    {
        $company = $this->company->find($id);

        return $company;
    }

    public function delete($id)
    {
        $deleted = $this->company->delete($id);

        return $deleted;
    }

    public function getAllBranchesByCompanyId($companyId)
    {
        $branches = $this->branch->getAllByCompanyId($companyId);

        return $branches;
    }

    public function updateStaff($staffId, array $data)
    {
        $updated = $this->branchStaff->updateByStaffId($staffId, $data);

        return $updated;
    }

    public function getStaffIdByUserId($userId)
    {
        $branchStaff = $this->branchStaff->findByUserId($userId);

        if(!$branchStaff){
            return null;
        }

        $staffId = $branchStaff->staff_id;

        return $staffId;
    }

    public function findStaffByUserId($userId)
    {
        $branchStaff = $this->branchStaff->findByUserId($userId);

        return $branchStaff;
    }

    public function getDefaultVat($companyId = null)
    {
        if(!$companyId){
            return env('DEFAULT_VAT', StatusHelper::DEFAULT_VAT);
        }

        $vat = $this->company->getDefaultVatById($companyId);

        return $vat;

    }

    public function getDefaultLowInventoryThreshold($companyId = null)
    {
        if(!$companyId){
            return env('DEFAULT_LOW_THRESHOLD', StatusHelper::DEFAULT_LOW_THRESHOLD);
        }

        $threshold = $this->company->getDefaultLowInventoryThresholdById($companyId);

        return $threshold;

    }

    public function getDefaultVatByBranchId($branchId)
    {
        $company = $this->company->findByBranchId($branchId);

        if(!$company){
            return env('DEFAULT_VAT', StatusHelper::DEFAULT_VAT);
        }

        $companyId = $company->id;

        return $this->getDefaultVat($companyId);


    }

    public function getDefaultLowInventoryThresholdByBranchId($branchId)
    {
        $company = $this->company->findByBranchId($branchId);

        if(!$company){
            return env('DEFAULT_LOW_THRESHOLD', StatusHelper::DEFAULT_LOW_THRESHOLD);
        }

        $companyId = $company->id;

        return $this->getDefaultLowInventoryThreshold($companyId);


    }

    public function canVoid($userId)
    {
        $roles = $this->userRole->getRolesByUserId($userId);

        if(in_array(StatusHelper::ADMIN, $roles) || in_array(StatusHelper::COMPANY, $roles)){
            return true;
        }

        $staff = $this->branchStaff->findByUserId($userId);

        if(!$staff){
            return false;
        }

        $canVoid = $staff->can_void;

        if($canVoid){
            return true;
        }

        return false;

    }

    public function isBranchFranchisee($branchId)
    {
        $branch = $this->branch->find($branchId);

        if(!$branch){
            return false;
        }

        $type = $branch->type;

        if($type != StatusHelper::FRANCHISEE){
            return false;
        }

        return true;
    }

}