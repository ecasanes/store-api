<?php namespace App\Mercury\Repositories;

use App\Mercury\Helpers\StatusHelper;
use App\Mercury\Helpers\SqlHelper;
use Illuminate\Support\Facades\DB;
use App\ActivityLogType as Type;
use Carbon\Carbon;

class ActivityLogTypeRepository implements ActivityLogTypeInterface
{
    public function getAll()
    {
        $types = Type::all();

        return $types;
    }

    public function find($id)
    {
        $type = Type::find($id);

        return $type;
    }

    public function findByCode($code)
    {
        $type = Type::where('code', $code)->first();

        return $type;
    }

    public function findByRoleId($roleId)
    {
        $type = Type::where('role_id', $roleId)->first();

        return $type;
    }

    public function filter(array $filter)
    {
        $activeFlag = StatusHelper::ACTIVE;

        $additionalFilters = $this->getAdditionalSqlFilters($filter);

        $query = "SELECT
                    activity_log_types.id,
                    activity_log_types.name,
                    activity_log_types.code,
                    activity_log_types.description,
                    activity_log_types.status,
                    activity_log_types.role_id,
                    activity_log_types.is_transaction,
                    activity_log_types.created_at
                  FROM
                    activity_log_types
                  WHERE
                    activity_log_types.status = '{$activeFlag}'
                  {$additionalFilters}
        ";

        $sql = DB::select($query);

        return $sql;

    }

    public function getAdditionalSqlFilters($filter)
    {
        $transactionActivityQuery = '';
        $activityLogTypesQuery = '';

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

        if(isset($filter['activity_log_types'])) {

            $activityLogTypes = explode(',',$filter['activity_log_types']);

            if(count($activityLogTypes)>0){
                $activityLogTypesQuery = " AND activity_log_types.code IN( '" . implode($activityLogTypes, "', '") . "' )";
            }

        }

        $additionalFilters = $transactionActivityQuery . $activityLogTypesQuery;

        return $additionalFilters;
    }
}