<?php namespace App\Mercury\Services;

use App\Mercury\Helpers\StatusHelper;
use App\Mercury\Repositories\ActivityLogTypeInterface;
use App\Mercury\Repositories\TransactionTypeInterface;
use App\Mercury\Repositories\TransactionInterface;
use App\Mercury\Repositories\ActivityLogInterface;
use App\Mercury\Repositories\BranchStaffInterface;
use App\Mercury\Repositories\UserRoleInterface;
use App\Mercury\Repositories\UserInterface;

use App\Mercury\Services\Traits\Activity\AuthenticationTrait;
use App\Mercury\Services\Traits\Activity\BranchTrait;
use App\Mercury\Services\Traits\Activity\CreateLogTrait;
use App\Mercury\Services\Traits\Activity\DeliveryTrait;
use App\Mercury\Services\Traits\Activity\MessageTrait;
use App\Mercury\Services\Traits\Activity\ProductTrait;
use App\Mercury\Services\Traits\Activity\StockTrait;
use App\Mercury\Services\Traits\Activity\SaleTrait;
use App\Mercury\Services\Traits\Activity\UserTrait;

class ActivityService
{
    protected $activityLogType;
    protected $transactionType;
    protected $transaction;
    protected $activityLog;
    protected $branchStaff;
    protected $userRole;
    protected $user;

    use AuthenticationTrait;
    use CreateLogTrait;
    use DeliveryTrait;
    use MessageTrait;
    use ProductTrait;
    use BranchTrait;
    use StockTrait;
    use SaleTrait;
    use UserTrait;

    public function __construct(
        TransactionTypeInterface $transactionType,
        ActivityLogTypeInterface $activityLogType,
        BranchStaffInterface $branchStaff,
        TransactionInterface $transaction,
        ActivityLogInterface $activityLog,
        UserRoleInterface $userRole,
        UserInterface $user
    )
    {
        $this->activityLogType = $activityLogType;
        $this->branchStaff = $branchStaff;
        $this->transactionType = $transactionType;
        $this->transaction = $transaction;
        $this->activityLog = $activityLog;
        $this->userRole = $userRole;
        $this->user = $user;
    }

    /*
    |--------------------------------------------------------------------------
    | ACTIVITY LOG Methods
    |--------------------------------------------------------------------------
    */

    public function getAll()
    {
        $activityLogs = $this->activityLog->getAll();

        return $activityLogs;
    }

    public function find($id)
    {
        $log = $this->activityLog->find($id);

        if(!$log) {
            return $log;
        }

        return $log;
    }

    public function filter(array $data)
    {
        $activityLogs = $this->activityLog->filter($data);

        return $activityLogs;
    }

    public function getFilterMeta($data)
    {
        $meta = $this->activityLog->getFilterMeta($data);

        return $meta;
    }

    public function create(array $data)
    {
        $log = $this->activityLog->create($data);

        return $log;
    }

    /*
    |--------------------------------------------------------------------------
    | ACTIVITY LOG TYPE Methods
    |--------------------------------------------------------------------------
    */

    public function getAllActivityLogTypes()
    {
        $types = $this->activityLogType->getAll();

        return $types;
    }

    public function findActivityLogType($id)
    {
        $type = $this->activityLogType->find($id);

        return $type;
    }

    public function findActivityLogTypeByCode($code)
    {
        $type = $this->activityLogType->findByCode($code);

        return $type;
    }

    public function findActivityLogTypeByRoleId($roleId)
    {
        $type = $this->activityLogType->findByRoleId($roleId);

        return $type;
    }

    public function filterActivityLogTypes($filters)
    {
        $types = $this->activityLogType->filter($filters);

        return $types;
    }

    public function logActivityByTransactionDetails($transactionId, $staffId, $transactionTypeId)
    {
        $transactionType = $this->transactionType->find($transactionTypeId);

        if(!$transactionType){
            return false;
        }

        $code = $transactionType->code;

        switch($code){
            case StatusHelper::SALE:
                $this->logAddSale($staffId, $transactionId);
                break;
            case StatusHelper::VOID_SALE:
                $this->logVoidSaleByStaff($staffId, $transactionId);
                break;
            case StatusHelper::RETURN_SALE:
                $this->logVoidReturnSaleByStaff($staffId, $transactionId);
                break;
            case StatusHelper::VOID_RETURN:
                $this->logVoidReturnSaleByStaff($staffId, $transactionId);
                break;
        }

        return true;
    }
}