<?php namespace App\DTIStore\Services\Traits\Activity;

trait CreateLogTrait {

    public function createLog($user, $role = null, $activityLogType, $transaction = null)
    {
        $branchId = null;

        $message = null;

        $roleId = null;

        $transactionArray = [];

        if($role && !empty($role)) {
            $roleId = $role->role_id;
        }

        if($user->branch_id_registered) {
            $branchId = $user->branch_id_registered;
        }

        if($transaction && !empty($transaction)) {
            $transactionArray = [
                'transaction_id' => $transaction->id
            ];

            $branchId = $transaction->branch_id;
        }

        $message = $this->setMessageData($user, $activityLogType->code, $transaction);

        $this->create([
            'activity_log_type_id' => $activityLogType->id,
            'user_id' => $user->id,
            'branch_id' => $branchId,
            'role_id' => $roleId,
            'readable_message' => $message
        ] + $transactionArray);
    }

    public function logMessage($message, $userId, $branchId = null)
    {
        $activityLogTypeCode = 'notification';

        $activityLogType = $this->activityLogType->findByCode($activityLogTypeCode);

        $role = $this->userRole->getRoleByUserId($userId);

        $this->create([
            'activity_log_type_id' => $activityLogType->id,
            'user_id' => $userId,
            'branch_id' => $branchId,
            'role_id' => $role->id,
            'readable_message' => $message
        ]);
    }

    public function logMessageByStaff($message, $staffId, $branchId = null)
    {
        $activityLogTypeCode = 'notification';

        $activityLogType = $this->activityLogType->findByCode($activityLogTypeCode);

        $user = $this->branchStaff->findUserByStaffId($staffId);

        $role = $this->userRole->getRoleByUserId($user->id);

        $this->create([
            'activity_log_type_id' => $activityLogType->id,
            'user_id' => $user->id,
            'branch_id' => $branchId,
            'role_id' => $role->id,
            'readable_message' => $message
        ]);

    }

    public function logInventoryLow($message, $userId, $branchId = null)
    {
        $activityLogTypeCode = 'inventory_low';

        $activityLogType = $this->activityLogType->findByCode($activityLogTypeCode);

        $role = $this->userRole->getRoleByUserId($userId);

        $this->create([
            'activity_log_type_id' => $activityLogType->id,
            'user_id' => $userId,
            'branch_id' => $branchId,
            'role_id' => $role->id,
            'readable_message' => $message
        ]);
    }

    public function logInventoryLowByStaff($message, $staffId, $branchId = null)
    {
        $activityLogTypeCode = 'inventory_low';

        $activityLogType = $this->activityLogType->findByCode($activityLogTypeCode);

        $user = $this->branchStaff->findUserByStaffId($staffId);

        $role = $this->userRole->getRoleByUserId($user->id);

        $this->create([
            'activity_log_type_id' => $activityLogType->id,
            'user_id' => $user->id,
            'branch_id' => $branchId,
            'role_id' => $role->id,
            'readable_message' => $message
        ]);
    }

}