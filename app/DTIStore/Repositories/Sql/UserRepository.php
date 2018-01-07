<?php namespace App\DTIStore\Repositories;

use App\DTIStore\Helpers\SqlHelper;
use App\DTIStore\Helpers\StatusHelper;
use App\Permission;
use App\User;
use App\UserPermission;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserRepository implements UserInterface
{

    public function create(array $data)
    {
        try {
            $user = User::create($data);
        } catch (Exception $e) {
            return false;
        }

        return $user;
    }

    public function find($id)
    {
        $user = User::where('users.id', $id)
            ->leftJoin('user_roles', 'user_roles.user_id', '=', 'users.id')
            ->leftJoin('roles', 'roles.id', '=', 'user_roles.role_id')
            ->select(
                'users.*',
                'roles.name as role_name',
                DB::raw("(SELECT store_id FROM user_stores WHERE user_id = {$id} LIMIT 1) as store_id")
            )
            ->first();

        return $user;
    }

    public function getAll()
    {
        $users = User::whereIn('status', [StatusHelper::ACTIVE])->get();

        return $users;
    }

    public function getCountAll()
    {
        $count = User::whereIn('status', [StatusHelper::ACTIVE])->count();

        return $count;
    }

    public function getCountByFilter(array $filter = [])
    {
        $activeFlag = StatusHelper::ACTIVE;
        $additionalSqlFilters = $this->getAdditionalSqlFilters($filter);

        $sql = "SELECT 
                    count(*) as users_count
                  FROM users 
                    INNER JOIN user_roles ON user_roles.`user_id` = users.`id`
                    INNER JOIN roles ON roles.`id` = user_roles.`role_id`
                  WHERE users.status = '{$activeFlag}'
                  {$additionalSqlFilters}
               ";

        $users = DB::select($sql);

        return $users[0]->users_count;
    }

    public function filter(array $filter)
    {
        $activeFlag = StatusHelper::ACTIVE;
        $saleFlag = StatusHelper::SALE;

        $additionalSqlFilters = $this->getAdditionalSqlFilters($filter);
        $paginationSql = SqlHelper::getPaginationByFilter($filter);

        $sql = "SELECT 
                    users.id,
                    users.firstname,
                    users.lastname,
                    users.middlename,
                    roles.name as role_name,
                    roles.code as role_code,
                    users.email,
                    users.phone,
                    users.city,
                    users.province,
                    users.zip,
                    users.address,
                    users.created_at,
                    users.status,
                    (SELECT TRIM(GROUP_CONCAT(' ',permissions.name)) 
                        FROM user_permissions 
                        INNER JOIN permissions ON permissions.id = user_permissions.permission_id 
                        WHERE user_permissions.user_id = users.id
                    ) as permission_names
                  FROM users 
                    INNER JOIN user_roles ON user_roles.`user_id` = users.`id`
                    INNER JOIN roles ON roles.`id` = user_roles.`role_id`
                  WHERE users.status IN ('{$activeFlag}','inactive')
                  {$additionalSqlFilters}
                  {$paginationSql}";
        $users = DB::select($sql);

        return $users;
    }

    public function update($id, $data)
    {
        $user = User::find($id);

        if (!$user) {
            return false;
        }

        if(isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $updated = $user->update($data);

        return $updated;
    }

    public function delete($id)
    {

        $user = User::find($id);

        if (!$user) {
            return false;
        }

        if ($user->status == StatusHelper::DELETED) {
            return true;
        }

        $deleted = $user->update([
            'deleted_at' => Carbon::now()->toDateTimeString(),
            'status' => StatusHelper::DELETED,
            'email' => $user->email . StatusHelper::flagDelete($user->name),
        ]);

        return $deleted;
    }

    public function destroy($id)
    {
        $user = User::find($id);

        if (!$user) {
            return false;
        }

        $destroyed = $user->delete();

        return $destroyed;
    }

    public function isDeleted($id)
    {
        $user = User::find($id);

        if (!$user) {
            return true;
        }

        if ($user->status != StatusHelper::DELETED) {
            return false;
        }

        return true;
    }

    public function getFilterMeta($data)
    {
        $length = $this->getCountByFilter($data);

        return [
                'length' => $length,
            ] + $data;
    }

    public function findByFirstnameAndLastname($firstname, $lastname)
    {
        $user = User::where(DB::raw("LOWER(firstname)"), strtolower($firstname))->where(DB::raw("LOWER(firstname)"), strtolower($lastname))->first();

        return $user;
    }

    private function getAdditionalSqlFilters($filter)
    {
        $searchKeySql = "";
        $storeSql = "";
        $roleSql = "";
        $staffSql = "";
        $rolesSql = "";

        $fromSql = "";
        $toSql = "";
        $orderSql = "";
        $sortSql = " ORDER BY users.`firstname` ASC";
        $privilegeSql = "";

        if (isset($filter['role'])) {
            $role = $filter['role'];
            $roleSql = " AND roles.code = '{$role}' ";
        }

        if (isset($filter['q'])) {

            $searchKey = $filter['q'];

            $searchKeySql = "";
        }

        if(isset($filter['order'])) {

            $order = strtoupper($filter['order']);

            if($order == 'ASC') {
                $orderSql = 'ASC';
            }

            if($order == 'DESC' || empty($order)) {
                $orderSql = 'DESC';
            }

        }

        if(!isset($filter['order'])) {
            $orderSql = 'DESC';
        }

        if(isset($filter['sort'])) {

            $sort = $filter['sort'];

            switch ($sort) {

                case 'sold_items':
                    $sortSql = " ORDER BY sold_items {$orderSql} ";
                    break;

                case 'lastname':
                    $sortSql = " ORDER BY users.`lastname` {$orderSql} ";
                    break;

                case 'firstname':
                    $sortSql = " ORDER BY users.`firstname` {$orderSql} ";
                    break;

                default:
                    $sortSql = " ORDER BY sold_items DESC";
            }
        }

        if (isset($filter['roles'])) {

            $roles = explode(',', $filter['roles']);

            $rolesSql .= " AND (";
            $rolesSqlArray = [];

            foreach ($roles as $role) {
                $rolesSqlArray[] = " roles.code = '{$role}' ";
            }

            $rolesSql .= implode(" OR ", $rolesSqlArray);
            $rolesSql .= ") ";

        }

        if (isset($filter['from'])) {
            $from = $filter['from'];
            $fromSql = " AND users.`created_at` >= DATE('{$from}') ";
        }

        if (isset($filter['to'])) {
            $to = $filter['to'];
            $toSql = " AND users.`created_at` <= DATE('{$to}') ";
        }

        if (isset($filter['range'])) {

            $range = $filter['range'];
            $now = Carbon::now();

            switch ($range) {
                case 'month':
                case 'monthly':
                    $from = $now->startOfMonth()->toDateTimeString();
                    $to = $now->endOfMonth()->toDateTimeString();
                    $fromSql = " AND users.`created_at` >= DATE('{$from}') ";
                    $toSql = " AND users.`created_at` <= DATE('{$to}') ";
                    break;
                    break;
                case 'year':
                case 'yearly':
                    $from = $now->startOfYear()->toDateTimeString();
                    $to = $now->endOfYear()->toDateTimeString();
                    $fromSql = " AND users.`created_at` >= DATE('{$from}') ";
                    $toSql = " AND users.`created_at` <= DATE('{$to}') ";
                    break;
                default:
                    //defaults to month
                    $from = $now->startOfMonth()->toDateTimeString();
                    $to = $now->endOfMonth()->toDateTimeString();
                    $fromSql = " AND users.`created_at` >= DATE('{$from}') ";
                    $toSql = " AND users.`created_at` <= DATE('{$to}') ";
                    break;
            }
        }

        $additionalSql = $searchKeySql . $roleSql . $storeSql .
            $privilegeSql . $staffSql . $rolesSql . $fromSql . $toSql .$sortSql;

        return $additionalSql;
    }

}