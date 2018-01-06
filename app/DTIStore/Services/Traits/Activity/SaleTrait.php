<?php namespace App\DTIStore\Services\Traits\Activity;

trait SaleTrait {

    public function setSaleLogData($userId, $activityLogTypeCode, $transactionId)
    {
        $user = $this->user->find($userId);

        $role = $this->userRole->getRoleByUserId($user->id);

        $activityLogType = $this->findActivityLogTypeByCode($activityLogTypeCode);

        $transaction = $this->transaction->find($transactionId);

        $this->createLog($user, $role, $activityLogType, $transaction);
    }

    public function logAddSale($staffId, $transactionId)
    {
        $user = $this->branchStaff->findUserByStaffId($staffId);

        $userId = $user->id;

        $activityLogTypeCode = 'add_sale';

        $this->setSaleLogData($userId, $activityLogTypeCode, $transactionId);
    }

    public function logReturnSale($staffId, $transactionId)
    {
        $user = $this->branchStaff->findUserByStaffId($staffId);

        $userId = $user->id;

        $activityLogTypeCode = 'return_sale';

        $this->setSaleLogData($userId, $activityLogTypeCode, $transactionId);
    }

    public function logVoidReturnSale($userId, $transactionId)
    {
        $activityLogTypeCode = 'return_sale';

        $this->setSaleLogData($userId, $activityLogTypeCode, $transactionId);
    }

    public function logShortoverSale($staffId, $transactionId)
    {
        $user = $this->branchStaff->findUserByStaffId($staffId);

        $userId = $user->id;

        $activityLogTypeCode = 'shortover_sale';

        $this->setSaleLogData($userId, $activityLogTypeCode, $transactionId);
    }

    public function logShortSale($staffId, $transactionId)
    {
        $user = $this->branchStaff->findUserByStaffId($staffId);

        $userId = $user->id;

        $activityLogTypeCode = 'short_sale';

        $this->setSaleLogData($userId, $activityLogTypeCode, $transactionId);
    }

    public function logVoidSaleByStaff($staffId, $transactionId)
    {
        $user = $this->branchStaff->findUserByStaffId($staffId);

        $userId = $user->id;

        $activityLogTypeCode = 'void_sale';

        $this->setSaleLogData($userId, $activityLogTypeCode, $transactionId);
    }

    public function logVoidSale($userId, $transactionId)
    {
        $activityLogTypeCode = 'void_sale';

        $this->setSaleLogData($userId, $activityLogTypeCode, $transactionId);
    }

    public function logVoidReturnSaleByStaff($staffId, $transactionId)
    {
        $user = $this->branchStaff->findUserByStaffId($staffId);

        $userId = $user->id;

        $activityLogTypeCode = 'void_return_sale';

        $this->setSaleLogData($userId, $activityLogTypeCode, $transactionId);
    }

    public function logVoidShortSale($staffId, $transactionId)
    {
        $user = $this->branchStaff->findUserByStaffId($staffId);

        $userId = $user->id;

        $activityLogTypeCode = 'void_short_sale';

        $this->setSaleLogData($userId, $activityLogTypeCode, $transactionId);
    }

    public function logVoidShortoverSale($staffId, $transactionId)
    {
        $user = $this->branchStaff->findUserByStaffId($staffId);

        $userId = $user->id;

        $activityLogTypeCode = 'void_shortover_sale';

        $this->setSaleLogData($userId, $activityLogTypeCode, $transactionId);
    }

    public function logAdjustmentShortByStaff($staffId, $transactionId)
    {
        $user = $this->branchStaff->findUserByStaffId($staffId);

        $userId = $user->id;

        $activityLogTypeCode = 'short_adjustment';

        $this->setSaleLogData($userId, $activityLogTypeCode, $transactionId);
    }

    public function logAdjustmentShort($userId, $transactionId)
    {
        $activityLogTypeCode = 'short_adjustment';

        $this->setSaleLogData($userId, $activityLogTypeCode, $transactionId);
    }

    public function logAdjustmentShortOverByStaff($staffId, $transactionId)
    {
        $user = $this->branchStaff->findUserByStaffId($staffId);

        $userId = $user->id;

        $activityLogTypeCode = 'shortover_adjustment';

        $this->setSaleLogData($userId, $activityLogTypeCode, $transactionId);
    }

    public function logAdjustmentShortover($userId, $transactionId)
    {
        $activityLogTypeCode = 'shortover_adjustment';

        $this->setSaleLogData($userId, $activityLogTypeCode, $transactionId);
    }

    public function logAdjustmentVoidShortByStaff($staffId, $transactionId)
    {
        $user = $this->branchStaff->findUserByStaffId($staffId);

        $userId = $user->id;

        $activityLogTypeCode = 'void_short_adjustment';

        $this->setSaleLogData($userId, $activityLogTypeCode, $transactionId);
    }

    public function logAdjustmentVoidShort($userId, $transactionId)
    {
        $activityLogTypeCode = 'void_short_adjustment';

        $this->setSaleLogData($userId, $activityLogTypeCode, $transactionId);
    }

    public function logAdjustmentVoidShortOverByStaff($staffId, $transactionId)
    {
        $user = $this->branchStaff->findUserByStaffId($staffId);

        $userId = $user->id;

        $activityLogTypeCode = 'void_short_adjustment';

        $this->setSaleLogData($userId, $activityLogTypeCode, $transactionId);
    }

    public function logAdjustmentVoidShortOver($userId, $transactionId)
    {
        $activityLogTypeCode = 'void_shortover_adjustment';

        $this->setSaleLogData($userId, $activityLogTypeCode, $transactionId);
    }

}