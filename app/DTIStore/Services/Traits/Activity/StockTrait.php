<?php namespace App\DTIStore\Services\Traits\Activity;

trait StockTrait {

    public function setStockLogData($userId, $activityLogTypeCode, $transactionId)
    {
        $user = $this->user->find($userId);

        $role = $this->userRole->getRoleByUserId($user->id);

        $activityLogType = $this->findActivityLogTypeByCode($activityLogTypeCode);

        $transaction = $this->transaction->find($transactionId);

        $this->createLog($user, $role, $activityLogType, $transaction);

    }

    public function logRestock($userId, $transactionId)
    {
        $activityLogTypeCode = 'restock_product';

        $this->setStockLogData($userId, $activityLogTypeCode, $transactionId);
    }

    public function logSubtractStock($userId, $transactionId)
    {
        $activityLogTypeCode = 'subtract_product';

        $this->setStockLogData($userId, $activityLogTypeCode, $transactionId);
    }

    public function logDeliverStock($userId, $transactionId)
    {
        $activityLogTypeCode = 'deliver_stock';

        $this->setStockLogData($userId, $activityLogTypeCode, $transactionId);
    }

    public function logReturnStock($userId, $transactionId)
    {
        $activityLogTypeCode = 'return_stock';

        $this->setStockLogData($userId, $activityLogTypeCode, $transactionId);
    }

    public function logRequestStock($userId, $transactionId)
    {
        $activityLogTypeCode = 'return_stock';

        $this->setStockLogData($userId, $activityLogTypeCode, $transactionId);
    }

}