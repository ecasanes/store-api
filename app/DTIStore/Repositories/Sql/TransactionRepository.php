<?php namespace App\DTIStore\Repositories;

use App\DTIStore\Helpers\SqlHelper;
use App\DTIStore\Helpers\StatusHelper;
use App\Transaction;
use App\TransactionItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TransactionRepository implements TransactionInterface
{

    public function create(array $data)
    {
        $transaction = Transaction::create($data);

        return $transaction;
    }

    public function find($id)
    {
        $transaction = Transaction::find($id);

        return $transaction;
    }

    public function update($id, $data)
    {
        $transaction = Transaction::find($id);

        if (!$transaction) {
            return false;
        }

        $updated = $transaction->update($data);

        return $updated;
    }

    public function delete($id)
    {
        $transaction = Transaction::find($id);

        if (!$transaction) {
            return false;
        }

        if ($transaction->status == StatusHelper::DELETED) {
            return true;
        }

        $deleted = $transaction->update([
            'deleted_at' => Carbon::now()->toDateTimeString(),
            'status' => StatusHelper::DELETED,
        ]);

        return $deleted;
    }

    public function destroy($id)
    {
        $transaction = Transaction::find($id);

        if (!$transaction) {
            return false;
        }

        $destroyed = $transaction->delete();

        return $destroyed;
    }

    public function isDeleted($id)
    {
        $transaction = Transaction::find($id);

        if (!$transaction) {
            return true;
        }

        if ($transaction->status != StatusHelper::DELETED) {
            return false;
        }

        return true;
    }

    public function findByTrackingNo($trackingNo)
    {
        $transaction = Transaction::where('tracking_no', $trackingNo)->first();

        return $transaction;
    }

}