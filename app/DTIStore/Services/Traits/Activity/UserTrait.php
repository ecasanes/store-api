<?php namespace App\DTIStore\Services\Traits\Activity;

trait UserTrait {

    public function setUserLogData($userId, $activityLogTypeCode)
    {
        $user = $this->user->find($userId);

        $role = $this->userRole->getRoleByUserId($user->id);

        $activityLogType = $this->findActivityLogTypeByCode($activityLogTypeCode);

        $this->createLog($user, $role, $activityLogType);
    }

    # TODO: set activity log type code
    public function logCreateUser($userId)
    {
        $activityLogTypeCode = '';

        $this->setUserLogData($userId, $activityLogTypeCode);
    }

    # TODO: set activity log type code
    public function logUpdateUser($userId)
    {
        $activityLogTypeCode = '';

        $this->setUserLogData($userId, $activityLogTypeCode);
    }

    # TODO: set activity log type code
    public function logDeleteUser($userId)
    {
        $activityLogTypeCode = '';

        $this->setUserLogData($userId, $activityLogTypeCode);
    }

}