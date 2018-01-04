<?php namespace App\Mercury\Services\Traits\Activity;

trait ProductTrait {

    public function setProductLogData($userId, $activityLogTypeCode)
    {
        $user = $this->user->find($userId);

        $role = $this->userRole->getRoleByUserId($user->id);

        $activityLog = $this->findActivityLogTypeByCode($activityLogTypeCode);

        $this->createLog($user, $role, $activityLog);
    }

    public function logCreateProduct($userId)
    {
        $activityLogTypeCode = 'add_product';

        $this->setProductLogData($userId, $activityLogTypeCode);
    }

    public function logUpdateProduct($userId)
    {
        $activityLogTypeCode = 'update_product';

        $this->setProductLogData($userId, $activityLogTypeCode);
    }

    public function logDeleteProduct($userId)
    {
        $activityLogTypeCode = 'delete_product';

        $this->setProductLogData($userId, $activityLogTypeCode);
    }
    
}