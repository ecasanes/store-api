<?php namespace App\Mercury\Repositories;

use App\Mercury\Helpers\StatusHelper;
use App\TransactionItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TransactionItemRepository implements TransactionItemInterface {

    public function create(array $data)
    {
        $transactionItem = TransactionItem::create($data);

        return $transactionItem;
    }

    public function find($id)
    {
        $transactionItem = TransactionItem::find($id);

        return $transactionItem;
    }

    public function getAll()
    {
        $transactionItems = TransactionItem::whereIn('status', [StatusHelper::ACTIVE])->get();

        return $transactionItems;
    }

    public function filter(array $filter)
    {
        // TODO: filters
        $transactionItems = $this->getAll();

        return $transactionItems;
    }

    public function update($id, $data)
    {
        $transactionItem = $this->find($id);

        if(!$transactionItem){
            return false;
        }

        $updated = $transactionItem->update($data);

        return $updated;
    }

    public function delete($id)
    {
        $transactionItem = $this->find($id);

        if(!$transactionItem){
            return false;
        }

        if($transactionItem->status == StatusHelper::DELETED){
            return true;
        }

        $deleted = $transactionItem->update([
            'deleted_at' => Carbon::now()->toDateTimeString(),
            'status' => StatusHelper::DELETED,
        ]);

        return $deleted;
    }

    public function destroy($id)
    {
        $transactionItem = $this->find($id);

        if(!$transactionItem){
            return false;
        }

        $destroyed = $transactionItem->delete();

        return $destroyed;
    }

    public function isDeleted($id)
    {
        $transactionItem = $this->find($id);

        if(!$transactionItem){
            return true;
        }

        if($transactionItem->status != StatusHelper::DELETED){
            return false;
        }

        return true;
    }

    public function getByTransactionId($transactionId)
    {
        $items = TransactionItem::where('transaction_id', $transactionId)
            ->select(
                'transaction_items.*',
                'product_cost_price as cost_price',
                'product_metrics as metrics',
                'product_name as name',
                'product_selling_price as selling_price',
                'product_franchisee_price as franchisee_price',
                'product_size as size',
                'quantity as branch_quantity',
                DB::raw("ROUND(quantity*product_selling_price,2) as subtotal"),
                DB::raw("ROUND(quantity*product_franchisee_price,2) as franchisee_subtotal")
            )
            ->get();

        return $items;
    }

    public function getByOr($orNumber, $transactionCode = StatusHelper::SALE, $branchId = null)
    {
        $activeFlag = StatusHelper::ACTIVE;

        $items = TransactionItem::where('transaction_items.transaction_id','!=',null)
            ->join('transactions','transactions.id','=','transaction_items.transaction_id')
            ->join('transaction_types','transaction_types.id','=','transactions.transaction_type_id')
            ->where('transactions.or_no',$orNumber)
            ->where('transactions.status',$activeFlag)
            ->where('transaction_types.code',$transactionCode)
            ->select(
                'transaction_items.*',
                'transaction_items.product_cost_price as cost_price',
                'transaction_items.product_metrics as metrics',
                'transaction_items.product_name as name',
                'transaction_items.product_selling_price as selling_price',
                'transaction_items.product_franchisee_price as franchisee_price',
                'transaction_items.product_size as size',
                'transaction_items.quantity as branch_quantity',
                DB::raw("ROUND(transaction_items.quantity*transaction_items.product_selling_price,2) as subtotal"),
                DB::raw("ROUND(transaction_items.quantity*transaction_items.product_franchisee_price,2) as franchisee_subtotal")
            );

        if($branchId){
            $items = $items->where('transactions.branch_id','=',$branchId);
        }

        $items = $items->get();

        return $items;
    }

}