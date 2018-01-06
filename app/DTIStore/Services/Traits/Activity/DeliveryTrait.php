<?php namespace App\DTIStore\Services\Traits\Activity;

trait DeliveryTrait {

    public function setDeliveryLogData($userId, $activityLogTypeCode)
    {
        $user = $this->user->find($userId);

        $role = $this->userRole->getRoleByUserId($user->id);

        $activityLog = $this->findActivityLogTypeByCode($activityLogTypeCode);

        $this->createLog($user, $role, $activityLog);
    }

    public function logAddPendingDelivery($userId)
    {
        $activityLogTypeCode = 'add_pending_delivery';

        $this->setDeliveryLogData($userId, $activityLogTypeCode);
    }

    public function logConfirmPendingDelivery($userId)
    {
        $activityLogTypeCode = 'confirm_pending_delivery';

        $this->setDeliveryLogData($userId, $activityLogTypeCode);
    }

    public function logVoidPendingDelivery($userId)
    {
        $activityLogTypeCode = 'void_pending_delivery';

        $this->setDeliveryLogData($userId, $activityLogTypeCode);
    }

    public function logReturnPendingDelivery($userId)
    {
        $activityLogTypeCode = 'return_pending_delivery';

        $this->setDeliveryLogData($userId, $activityLogTypeCode);
    }

}