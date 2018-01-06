<?php namespace App\DTIStore\Repositories;

use App\DTIStore\Helpers\StatusHelper;
use App\Branch;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class StoreRepository implements StoreInterface
{

    public function create(array $data)
    {
        $branch = Branch::create($data);

        return $branch;
    }

    public function find($id)
    {
        $branchFlag = StatusHelper::BRANCH;

        $branch = Branch::where('branches.id', $id)
            ->leftJoin('branch_staffs', 'branch_staffs.branch_id', '=', 'branches.id')
            ->leftJoin('user_roles', 'user_roles.user_id', '=', 'branch_staffs.user_id')
            ->leftJoin('roles', function ($join) use ($branchFlag) {
                $join->on('roles.id', '=', 'user_roles.role_id');
                $join->on('roles.code', '=', DB::raw("'{$branchFlag}'"));
            })
            ->leftJoin('users', 'user_roles.user_id', '=', 'users.id')
            ->select('branches.*', 'users.id as owner_user_id')
            ->first();

        return $branch;
    }

    public function findByKey($key)
    {
        $branch = Branch::where('key', $key)->first();

        return $branch;
    }

    public function getAll()
    {
        $branchFlag = StatusHelper::BRANCH;
        $staffFlag = StatusHelper::STAFF;
        $activeFlag = StatusHelper::ACTIVE;

        // FIXME: optimize query

        $sql = "SELECT 
                  branches.*,
                  (SELECT 
                    COUNT(*) 
                  FROM
                    branch_staffs 
                    INNER JOIN user_roles 
                      ON user_roles.`user_id` = branch_staffs.`user_id` 
                    INNER JOIN roles 
                      ON roles.`id` = user_roles.`role_id` 
                    INNER JOIN users ON users.id = user_roles.user_id
                  WHERE branch_staffs.`branch_id` = branches.`id` 
                    AND roles.`code` = '{$staffFlag}' AND users.status = '{$activeFlag}') AS staff_count,
                  (SELECT 
                    users.firstname
                  FROM
                    users 
                    INNER JOIN user_roles 
                      ON user_roles.user_id = users.`id` 
                    INNER JOIN roles 
                      ON roles.`id` = user_roles.`role_id` 
                    INNER JOIN branch_staffs 
                      ON branch_staffs.`user_id` = user_roles.`user_id` 
                  WHERE roles.`code` = '{$branchFlag}' 
                    AND branch_staffs.`branch_id` = branches.`id` 
                  LIMIT 1) AS firstname,
                  (SELECT 
                    users.lastname
                  FROM
                    users 
                    INNER JOIN user_roles 
                      ON user_roles.user_id = users.`id` 
                    INNER JOIN roles 
                      ON roles.`id` = user_roles.`role_id` 
                    INNER JOIN branch_staffs 
                      ON branch_staffs.`user_id` = user_roles.`user_id` 
                  WHERE roles.`code` = '{$branchFlag}' 
                    AND branch_staffs.`branch_id` = branches.`id` 
                  LIMIT 1) AS lastname,
                  (SELECT 
                    users.email
                  FROM
                    users 
                    INNER JOIN user_roles 
                      ON user_roles.user_id = users.`id` 
                    INNER JOIN roles 
                      ON roles.`id` = user_roles.`role_id` 
                    INNER JOIN branch_staffs 
                      ON branch_staffs.`user_id` = user_roles.`user_id` 
                  WHERE roles.`code` = '{$branchFlag}' 
                    AND branch_staffs.`branch_id` = branches.`id` 
                  LIMIT 1) AS email,
                  (SELECT 
                    users.`id`
                  FROM
                    users 
                    INNER JOIN user_roles 
                      ON user_roles.user_id = users.`id` 
                    INNER JOIN roles 
                      ON roles.`id` = user_roles.`role_id` 
                    INNER JOIN branch_staffs 
                      ON branch_staffs.`user_id` = user_roles.`user_id` 
                  WHERE roles.`code` = '{$branchFlag}' 
                    AND branch_staffs.`branch_id` = branches.`id` 
                  LIMIT 1) AS owner_user_id 
                FROM
                  branches 
                WHERE branches.`status` = '{$activeFlag}' ";

        $branches = DB::select($sql);

        return $branches;
    }

    public function filter(array $filter)
    {
        $branchFlag = StatusHelper::BRANCH;
        $staffFlag = StatusHelper::STAFF;
        $activeFlag = StatusHelper::ACTIVE;

        $branchTypeSql = "";
        $keysSql = "";

        if(isset($filter['type'])){
            $branchType = $filter['type'];
            $branchTypeSql = " AND branches.type = '{$branchType}' ";
        }

        if(isset($filter['keys'])){
            $keys = explode(',',$filter['keys']);
            $keysSql = " AND branches.`key` IN( '" . implode($keys, "', '") . "' )";
        }

        $sql = "SELECT 
                  branches.*,
                  (SELECT 
                    COUNT(*) 
                  FROM
                    branch_staffs 
                    INNER JOIN user_roles 
                      ON user_roles.`user_id` = branch_staffs.`user_id` 
                    INNER JOIN roles 
                      ON roles.`id` = user_roles.`role_id` 
                    INNER JOIN users ON users.id = user_roles.user_id
                  WHERE branch_staffs.`branch_id` = branches.`id` 
                    AND roles.`code` = '{$staffFlag}' AND users.status = '{$activeFlag}') AS staff_count,
                  (SELECT 
                    users.firstname
                  FROM
                    users 
                    INNER JOIN user_roles 
                      ON user_roles.user_id = users.`id` 
                    INNER JOIN roles 
                      ON roles.`id` = user_roles.`role_id` 
                    INNER JOIN branch_staffs 
                      ON branch_staffs.`user_id` = user_roles.`user_id` 
                  WHERE roles.`code` = '{$branchFlag}' 
                    AND branch_staffs.`branch_id` = branches.`id` 
                  LIMIT 1) AS firstname,
                  (SELECT 
                    users.lastname
                  FROM
                    users 
                    INNER JOIN user_roles 
                      ON user_roles.user_id = users.`id` 
                    INNER JOIN roles 
                      ON roles.`id` = user_roles.`role_id` 
                    INNER JOIN branch_staffs 
                      ON branch_staffs.`user_id` = user_roles.`user_id` 
                  WHERE roles.`code` = '{$branchFlag}' 
                    AND branch_staffs.`branch_id` = branches.`id` 
                  LIMIT 1) AS lastname,
                  (SELECT 
                    users.email
                  FROM
                    users 
                    INNER JOIN user_roles 
                      ON user_roles.user_id = users.`id` 
                    INNER JOIN roles 
                      ON roles.`id` = user_roles.`role_id` 
                    INNER JOIN branch_staffs 
                      ON branch_staffs.`user_id` = user_roles.`user_id` 
                  WHERE roles.`code` = '{$branchFlag}' 
                    AND branch_staffs.`branch_id` = branches.`id` 
                  LIMIT 1) AS email,
                  (SELECT 
                    users.`id`
                  FROM
                    users 
                    INNER JOIN user_roles 
                      ON user_roles.user_id = users.`id` 
                    INNER JOIN roles 
                      ON roles.`id` = user_roles.`role_id` 
                    INNER JOIN branch_staffs 
                      ON branch_staffs.`user_id` = user_roles.`user_id` 
                  WHERE roles.`code` = '{$branchFlag}' 
                    AND branch_staffs.`branch_id` = branches.`id` 
                  LIMIT 1) AS owner_user_id 
                FROM
                  branches 
                WHERE branches.`status` = '{$activeFlag}' 
                {$branchTypeSql}
                {$keysSql}
                ";

        $branches = DB::select($sql);

        return $branches;
    }

    public function update($id, $data)
    {
        $branch = Branch::find($id);

        if (!$branch) {
            return false;
        }

        $updated = $branch->update($data);

        return $updated;
    }

    public function delete($id)
    {
        $branch = Branch::find($id);

        if (!$branch) {
            return false;
        }

        if ($branch->status == StatusHelper::DELETED) {
            return true;
        }

        $deleted = $branch->update([
            'deleted_at' => Carbon::now()->toDateTimeString(),
            'status' => StatusHelper::DELETED,
        ]);

        return $deleted;
    }

    public function destroy($id)
    {
        $branch = Branch::find($id);

        if (!$branch) {
            return false;
        }

        $destroyed = $branch->delete();

        return $destroyed;
    }

    public function isDeleted($id)
    {
        $branch = Branch::find($id);

        if (!$branch) {
            return true;
        }

        if ($branch->status != StatusHelper::DELETED) {
            return false;
        }

        return true;
    }

    public function getAllByCompanyId($companyId)
    {
        $branches = Branch::where('company_id', $companyId)
            ->where('status', StatusHelper::ACTIVE)
            ->get();

        return $branches;
    }
}