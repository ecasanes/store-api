<?php namespace App\Mercury\Repositories;

use App\Mercury\Helpers\StatusHelper;
use App\Mercury\Helpers\SqlHelper;
use Illuminate\Support\Facades\DB;
use App\ActivityLog as Log;
use Carbon\Carbon;

class ActivityLogRepository implements ActivityLogInterface
{
    public function getAll()
    {
        $activeFlag = StatusHelper::ACTIVE;

        $query = "SELECT
            activity_logs.id as activity_log_id,
            activity_logs.activity_log_type_id,
            activity_logs.user_id,
            activity_logs.user_firstname,
            activity_logs.user_lastname,
            activity_logs.user_email,
            activity_logs.user_phone,
            activity_logs.branch_id,
            activity_logs.role_name,
            activity_logs.transaction_type,
            activity_logs.transaction_status,
            activity_logs.status,
            activity_logs.transaction_type_id,
            activity_logs.transaction_type_name,
            activity_logs.readable_message as message,
            activity_logs.created_at as date,
            activity_log_types.name as type_name,
            activity_log_types.code as type_code,
            activity_log_types.is_transaction
        FROM
          activity_logs
          INNER JOIN activity_log_types
            ON activity_log_types.id = activity_logs.activity_log_type_id
        WHERE activity_logs.status = '{$activeFlag}'";

        $activityLogs = DB::select($query);

        return $activityLogs;
    }

    public function find($id)
    {
        $log = Log::find($id);

        if(!$log) {
            return $log;
        }

        return $log;
    }

    public function filter(array $filter)
    {
        $additionalSqlFilters = $this->getAdditionalSqlFilters($filter);

        $paginationSql = $paginationSql = SqlHelper::getPaginationByFilter($filter);

//        $now = Carbon::now()->toDateString();
//
//        $sevenDays = Carbon::now()->subDays(20)->toDateString();
        
        $activeFlag = StatusHelper::ACTIVE;

        $orderByFilter = $this->getOrderByFilter($filter);

        $query = "SELECT
            activity_logs.id as activity_log_id,
            activity_logs.activity_log_type_id,
            activity_logs.status,
            activity_logs.user_id,
            activity_logs.branch_id,
            branches.name as branch_name,
            users.firstname as user_firstname,
            users.firstname as user_firstname,
            users.lastname as user_lastname,
            users.email as user_email,
            users.phone as user_phone,
            user_roles.role_id,
            roles.name as role_name,
            activity_logs.readable_message as message,
            activity_logs.created_at as date,
            activity_log_types.name as activity_log_type_name,
            activity_log_types.code as activity_log_type_code,
            activity_log_types.id as activity_log_type_id,
            activity_log_types.is_transaction,
            transaction_types.id as transaction_type_id,
            transaction_types.name transaction_type_name,
            transaction_types.code as transaction_type,
            transactions.or_no as order_number,
            transactions.customer_firstname,
            transactions.customer_lastname,
            transactions.status as transaction_status
        FROM
          activity_logs
          LEFT JOIN activity_log_types
            ON activity_log_types.`id` = activity_logs.`activity_log_type_id`
          LEFT JOIN transactions
            ON transactions.`id` = activity_logs.`transaction_id`
          LEFT JOIN transaction_types
            ON transactions.`transaction_type_id` = transaction_types.`id` 
          LEFT JOIN users
            ON users.`id` = activity_logs.`user_id`
          LEFT JOIN user_roles
            ON user_roles.`user_id` = activity_logs.`user_id`
          LEFT JOIN roles
            ON user_roles.`role_id` = roles.`id`  
          LEFT JOIN branches
            ON branches.`id` = transactions.`branch_id`
        WHERE activity_logs.status = '{$activeFlag}'
        {$additionalSqlFilters}
        ORDER BY activity_logs.created_at {$orderByFilter}
        {$paginationSql}";
//        dd($query);
        $activityLogs = DB::select($query);

        return $activityLogs;
    }

    public function getOrderByFilter($filter)
    {
        if(isset($filter['order_by'])) {

            $filter = strtolower($filter['order_by']);

            if($filter == 'ascending' || $filter == 'asc') {
                return 'ASC';
            }
        }

        return 'DESC';
    }

    public function getCountAll()
    {
        $activeFlag = StatusHelper::ACTIVE;

        $query = "SELECT 
            count(*) as logs_count
            FROM 
              activity_logs 
              INNER JOIN activity_log_types
                ON activity_log_types.id = activity_logs.activity_log_type_id
            WHERE activity_logs.status = '{$activeFlag}' ";

        $activityLogs = DB::select($query);

        return $activityLogs[0]->logs_count;
    }

    public function getCountByFilter(array $filter = [])
    {
        $activeFlag = StatusHelper::ACTIVE;
        $additionalSqlFilters = $this->getAdditionalSqlFilters($filter);

        $sql = "SELECT
            count(*) as logs_count
                FROM
                  activity_logs
                  INNER JOIN activity_log_types
                    ON activity_log_types.id = activity_logs.activity_log_type_id
                WHERE activity_logs.status = '{$activeFlag}'
                {$additionalSqlFilters} ";

        $activityLogs = DB::select($sql);

        return $activityLogs[0]->logs_count;
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

    public function create(array $data)
    {
        $log = Log::create($data);

        return $log;
    }

//    public function update($id, $data)
//    {
//        $log = $this->find($id);
//
//        if(!$log) {
//            return false;
//        }
//
//        $updated = $log->update($data);
//
//        return $updated;
//    }

//    public function delete($id)
//    {
//        $log = $this->find($id);
//
//        if(!$log) {
//            return false;
//        }
//
//        if($log->status == StatusHelper::DELETED){
//            return true;
//        }
//
//        $deleted = $log->update([
//            'deleted_at' => Carbon::now()->toDateTimeString(),
//            'status' => StatusHelper::DELETED,
//            'name' => StatusHelper::flagDelete($log->name),
//            'code' => StatusHelper::flagDelete($log->code)
//        ]);
//
//        return $deleted;
//    }

//    public function destroy($id)
//    {
//        $log = $this->find($id);
//
//        if(!$log){
//            return false;
//        }
//
//        $destroyed = $log->delete();
//
//        return $destroyed;
//    }

//    public function isDeleted($id)
//    {
//        $log = $this->find($id);
//
//        if(!$log){
//            return true;
//        }
//
//        if($log->status != StatusHelper::DELETED){
//            return false;
//        }
//
//        return true;
//    }

    private function getAdditionalSqlFilters($filter)
    {
        $transactionActivityQuery = "";
        $activityLogTypeQuery = "";
        $transactionTypeQuery = "";
        $searchQuery = "";
        $branchQuery = "";
        $roleQuery = "";
        $userQuery = "";
        $fromQuery = "";
        $toQuery = "";
        $activityLogTypesQuery = "";

        if(isset($filter['role_name'])) {
            $roleName = $filter['role_name'];
            strtolower($roleName);
            $roleQuery = " AND roles.name = {$roleName} ";
        }

        if(isset($filter['user_id'])) {
            $userId = $filter['user_id'];
            $userQuery = " AND activity_logs.user_id = {$userId} ";
        }

        if(isset($filter['branch_id'])) {
            $branchId = $filter['branch_id'];
            $branchQuery = " AND activity_logs.branch_id = {$branchId} ";
        }

        if(isset($filter['transaction_type'])) {
            $transactionType = $filter['transaction_type'];
            strtolower($transactionType);
            $transactionTypeQuery = " AND activity_logs.transaction_type = {$transactionType} ";
        }

        if(isset($filter['activity_log_types'])) {

            $activityLogTypes = explode(',',$filter['activity_log_types']);

            if(count($activityLogTypes)>0){
                $activityLogTypesQuery = " AND activity_log_types.`code` IN( '" . implode($activityLogTypes, "', '") . "' )";
            }

        }

        if(isset($filter['activity_type_id'])) {
            $activityLogTypeId = $filter['activity_type_id'];
            $activityLogTypeQuery = " AND activity_logs.activity_log_type_id = {$activityLogTypeId} ";
        }

        if(isset($filter['is_transaction'])) {

            $transactionTypeFilter = $filter['is_transaction'];

            if($transactionTypeFilter != 0 && $transactionTypeFilter != 1
                && $transactionTypeFilter != '0' && $transactionTypeFilter != '1') {

                $transactionTypeFilter = strtolower($transactionTypeFilter);

                if($transactionTypeFilter == 'true') {

                    $transactionTypeFilter = 1;

                } elseif($transactionTypeFilter == 'false') {

                    $transactionTypeFilter = 0;

                } else {

                    $transactionTypeFilter = '';

                }

            }

            $transactionActivityQuery = " AND activity_log_types.is_transaction = {$transactionTypeFilter} ";
        }

        if(isset($filter['q'])) {
            $query = $filter['q'];
            $searchQuery = " AND LOWER(
                CONCAT(
                    activity_logs.user_firstname,
                    ' ',
                    activity_logs.user_lastname,
                    ' ',
                    activity_logs.user_email,
                    ' '
                )
            ) 
            LIKE LOWER('%{$query}%') ";
        }

        if(isset($filter['from'])) {
            $from = $filter['from'];
            $fromQuery = " AND activity_logs.created_at >= DATE('{$from}') ";
        }

        if(isset($filter['to'])) {
            $to = $filter['to'];
            $toQuery = " AND activity_logs.created_at <= DATE('{$to}') ";
        }

        // TODO: retrieve previous transactions in case there are none for this week
        if(!isset($filter['from']) && !isset($filter['to'])) {

            $from = Carbon::now()->subDays(7)->toDateTimeString();

            $to = Carbon::now()->addDays(1)->toDateTimeString();

            $fromQuery = " AND activity_logs.`created_at` >= DATE('{$from}') ";

            $toQuery = " AND activity_logs.`created_at` <= DATE('{$to}') ";

        }

        $additionalSql = $roleQuery . $userQuery . $branchQuery . $transactionTypeQuery . $searchQuery.
            $activityLogTypeQuery. $transactionActivityQuery . $fromQuery . $toQuery . $activityLogTypesQuery;

        return $additionalSql;
    }

//    private function checkTransactionsForThisWeek($start, $end)
//    {
//        $query = "SELECT count(*)
//                  FROM activity_logs as logs_count
//                  WHERE activity_logs.created_at >= DATE('{$start}') AND activity_logs.created_at <= DATE('{$end}')
//                  ORDER BY activity_logs.created_at DESC";
//
//        $sql = DB::select($query);
//
//        if(empty($sql) || !$sql) {
//
//
//        }
//
//        return true;
//    }

//    private function getBackupTransactions()
//    {
//        $query = "SELECT activity_logs.*
//                      FROM activity_logs
//                      ORDER BY activity_logs.created_at DESC";
//
//        $logs = DB::select($query);
//
//        $date = $logs[0]['created_at'];
//
//
//    }
}