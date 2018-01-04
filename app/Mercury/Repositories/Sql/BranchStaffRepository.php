<?php namespace App\Mercury\Repositories;

use App\Branch;
use App\Mercury\Helpers\SqlHelper;
use App\Mercury\Helpers\StatusHelper;
use App\BranchStaff;
use Carbon\Carbon;

class BranchStaffRepository implements BranchStaffInterface
{

    public function create(array $data)
    {
        $branchStaff = BranchStaff::create($data);

        return $branchStaff;
    }

    public function find($id)
    {
        $branchStaff = BranchStaff::find($id);

        return $branchStaff;
    }

    public function findByStaffId($staffId)
    {
        $branchStaff = BranchStaff::where('staff_id', $staffId)->first();

        return $branchStaff;
    }

    public function getAll()
    {
        $branchStaffs = BranchStaff::whereIn('status', [StatusHelper::ACTIVE])->get();

        return $branchStaffs;
    }

    public function filter(array $filter)
    {
        // TODO:

        $additionalSqlFilters = $this->getAdditionalSqlFilters($filter);
        $paginationSql = SqlHelper::getPaginationByFilter($filter);
        $activeFlag = StatusHelper::ACTIVE;

        $query = "SELECT
                    branch_staffs.id as branch_staff_id,
                    branch_staffs.staff_id,
                    branch_staffs.branch_id,
                    branch_staffs.user_id,
                    branch_staffs.status as branch_staff_status,
                    users.firstname,
                    users.lastname,
                    users.email,
                    users.phone,
                    users.address,
                    branches.name as branch_name,
                    branches.address as branch_address,
                    branches.city,
                    branches.zip,
                    branches.province,
                    branches.phone as branch_phone,
                    branches.status as branch_status,
                    branches.override_default_store_time,
                    branches.default_start_time as branch_start_time,
                    branches.default_end_time as branch_end_time,
                    branches.company_id,
                    companies.id as company_id,
                    companies.name as company_name,
                    companies.default_start_time as company_start_time,
                    companies.default_end_time as company_end_time,
                    companies.default_color
                FROM branch_staffs
                  LEFT JOIN users
                    ON users.`id` = branch_staffs.`id`
                  LEFT JOIN branches
                    ON branches.`id` = branch_staffs.`branch_id`
                  LEFT JOIN companies
                    ON companies.`id` = branches.`company_id`
                WHERE branch_staffs.status = '{$activeFlag}'
                {$additionalSqlFilters}
                {$paginationSql}";

        $branchStaffs = DB::select($query);

        return $branchStaffs;
    }

    public function getAdditionalSqlFilters($filter)
    {
        $companyQuery = "";

        $branchQuery = "";

        $searchQuery = "";

        if(isset($filter['company_id'])) {
            $companyId = $filter['company_id'];
            $companyQuery = " AND companies.id = {$companyId} ";
        }

        if(isset($filter['branch_id'])) {
            $branchId = $filter['company_id'];
            $branchQuery = " AND branches.id = {$branchId} ";
        }

        if(isset($filter['q'])) {
            $query = $filter['q'];
            $searchQuery = " AND LOWER(
                CONCAT(
                    branches.name,
                    ' ',
                    companies.name,
                    ' ',
                    users.firstname,
                    ' ',
                    users.lastname,
                    ' ',
                    users.email,
                    ' ',
                )
            ) 
            LIKE LOWER('%{$query}%') ";

        }

        $additionalSqlFilters = $companyQuery . $branchQuery . $searchQuery;

        return $additionalSqlFilters;

    }

    public function getFilterMeta($filter)
    {
        $limit = SqlHelper::getLimitByFilter($filter);
        $page = SqlHelper::getPageByFilter($filter);

        return [
            'length' => $this->getCountByFilter($filter),
            'limit' => $limit,
            'page' => $page
        ];
    }

    public function update($id, $data)
    {
        $branchStaff = $this->find($id);

        if (!$branchStaff) {
            return false;
        }

        $updated = $branchStaff->update($data);

        return $updated;
    }

    public function updateByUserId($id, $data)
    {
        $branchStaff = $this->findByUserId($id);

        if (!$branchStaff) {
            return false;
        }

        $updated = $branchStaff->update($data);

        return $updated;
    }

    public function delete($id)
    {
        $branchStaff = $this->find($id);

        if (!$branchStaff) {
            return false;
        }

        if ($branchStaff->status == StatusHelper::DELETED) {
            return true;
        }

        $deleted = $branchStaff->update([
            'deleted_at' => Carbon::now()->toDateTimeString(),
            'status' => StatusHelper::DELETED,
        ]);

        return $deleted;
    }

    public function destroy($id)
    {
        $branchStaff = $this->find($id);

        if (!$branchStaff) {
            return false;
        }

        $destroyed = $branchStaff->delete();

        return $destroyed;
    }

    public function isDeleted($id)
    {
        $branchStaff = $this->find($id);

        if (!$branchStaff) {
            return true;
        }

        if ($branchStaff->status != StatusHelper::DELETED) {
            return false;
        }

        return true;
    }

    public function findUserByKeyAndStaffId($key, $staffId)
    {
        $user = Branch::where('branches.key', $key)
            ->join('branch_staffs', 'branch_staffs.branch_id', '=', 'branches.id')
            ->join('users', 'users.id', '=', 'branch_staffs.user_id')
            ->where('branch_staffs.staff_id', $staffId)
            ->select(
                'users.*',
                'branch_staffs.staff_id',
                'branch_staffs.can_void',
                'branch_staffs.has_multiple_access'
            )
            ->first();

        return $user;
    }

    public function generateStaffId($userId, $branchId)
    {
        $branchStaff = $this->findByUserAndBranch($userId, $branchId);

        if ($branchStaff) {
            return $branchStaff->staff_id;
        }

        $StaffIdNotUnique = true;

        $staffId = 0;

        while($StaffIdNotUnique){

            $staffId = rand(1000, 9999); // TODO: need helpers

            $branchStaffFromId = $this->findByStaffId($staffId);

            if ($branchStaffFromId) {
                continue;
            }

            $this->create([
                'staff_id' => $staffId,
                'user_id' => $userId,
                'branch_id' => $branchId
            ]);

            $StaffIdNotUnique = false;
        }

        return $staffId;

    }

    public function findUserByStaffId($staffId)
    {

        $user = BranchStaff::where('branch_staffs.staff_id', $staffId)
            ->join('users', 'users.id', '=', 'branch_staffs.user_id')
            ->select(
                'users.*',
                'branch_staffs.staff_id',
                'branch_staffs.can_void',
                'branch_staffs.has_multiple_access'
            )
            ->first();

        return $user;
    }

    public function updateByStaffId($staffId, $data)
    {
        $branchStaff = BranchStaff::where('staff_id', $staffId)->first();

        if(!$branchStaff){
            return false;
        }

        $updated = $branchStaff->update($data);

        return $updated;
    }

    public function findByUserId($userId)
    {
        $branchStaff = BranchStaff::where('user_id', $userId)->first();

        return $branchStaff;
    }

    private function findByUserAndBranch($userId, $branchId)
    {
        $branchStaff = BranchStaff::where('user_id', $userId)
            ->where('branch_id', $branchId)
            ->first();

        return $branchStaff;
    }
}