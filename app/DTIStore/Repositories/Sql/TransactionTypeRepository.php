<?php namespace App\DTIStore\Repositories;

use App\DTIStore\Helpers\StatusHelper;
use App\TransactionType;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TransactionTypeRepository implements TransactionTypeInterface {

    public function create(array $data)
    {
        $transactionType = TransactionType::create($data);

        return $transactionType;
    }

    public function find($id)
    {
        $transactionType = TransactionType::find($id);

        return $transactionType;
    }

    public function getAll()
    {
        $transactionTypes = TransactionType::whereIn('status', [StatusHelper::ACTIVE])->get();

        return $transactionTypes;
    }

    public function filter(array $filter)
    {
//        $transactionTypes = $this->getAll();

        $activeFlag = StatusHelper::ACTIVE;

        $additionalFilters = $this->getAdditionalFilters($filter);

        $query = "SELECT
                    transaction_types.*
                  FROM
                    transaction_types
                  WHERE
                    transaction_types.status = '{$activeFlag}'
                  {$additionalFilters}";

        $transactionTypes = DB::select($query);

        return $transactionTypes;
    }

    public function getAdditionalFilters($filters)
    {
        $groupFilter = "";

        if(isset($filters['group'])) {

            $group = $filters['group'];

            $groupFilter = " AND transaction_types.group = '{$group}'";
        }

        $additionalFilters = $groupFilter;

        return $additionalFilters;
    }

    public function update($id, $data)
    {
        $transactionType = $this->find($id);

        if(!$transactionType){
            return false;
        }

        $updated = $transactionType->update($data);

        return $updated;
    }

    public function delete($id)
    {
        $transactionType = $this->find($id);

        if(!$transactionType){
            return false;
        }

        if($transactionType->status == StatusHelper::DELETED){
            return true;
        }

        $deleted = $transactionType->update([
            'deleted_at' => Carbon::now()->toDateTimeString(),
            'status' => StatusHelper::DELETED,
        ]);

        return $deleted;
    }

    public function destroy($id)
    {
        $transactionType = $this->find($id);

        if(!$transactionType){
            return false;
        }

        $destroyed = $transactionType->delete();

        return $destroyed;
    }

    public function isDeleted($id)
    {
        $transactionType = $this->find($id);

        if(!$transactionType){
            return true;
        }

        if($transactionType->status != StatusHelper::DELETED){
            return false;
        }

        return true;
    }

    public function findByCode($transactionCode)
    {
        $transactionType = TransactionType::where('code', $transactionCode)->first();

        return $transactionType;
    }
}