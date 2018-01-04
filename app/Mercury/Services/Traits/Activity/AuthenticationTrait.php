<?php namespace App\Mercury\Services\Traits\Activity;

trait AuthenticationTrait {

    public function setAuthenticationLogData($userId, $code)
    {
        $activityLogType = $this->findActivityLogTypeByCode($code);

        $user = $this->user->find($userId);

        $role = $this->userRole->getRoleByUserId($user->id);

        $this->createLog($user, $role, $activityLogType);
    }

    public function logUserLogin($userId)
    {
        //$activityLogTypeCode = 'login';

        //$this->setAuthenticationLogData($userId, $activityLogTypeCode);

    }

    public function logUserLogout($userId)
    {
        //$activityLogTypeCode = 'logout';

        //$this->setAuthenticationLogData($userId, $activityLogTypeCode);

    }

}