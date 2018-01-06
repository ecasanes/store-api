<?php namespace App\DTIStore\Services\Traits\Activity;

trait BranchTrait {

    public function setBranchLogData($userId, $activityLogTypeCode)
    {
        $user = $this->user->find($userId);

        $role = $this->userRole->getRoleByUserId($user->id);

        $activityLogType = $this->findActivityLogTypeByCode($activityLogTypeCode);

        $this->createLog($user, $role, $activityLogType);
    }

    public function logCreateBranch($userId)
    {
        $activityLogTypeCode = 'add_branch';

        $this->setBranchLogData($userId, $activityLogTypeCode);
    }

    public function logUpdateBranch($userId)
    {
        $activityLogTypeCode = 'update_branch';

        $this->setBranchLogData($userId, $activityLogTypeCode);
    }

    public function logDeleteBranch($userId)
    {
        $activityLogTypeCode = 'delete_branch';

        $this->setBranchLogData($userId, $activityLogTypeCode);
    }
}