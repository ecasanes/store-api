<?php namespace App\DTIStore\Repositories;

use App\DTIStore\Helpers\StatusHelper;
use App\Order;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class OrderRepository implements OrderInterface
{

    public function create($data)
    {
        $order = Order::create($data);

        return $order;
    }

    public function find($id)
    {
        $order = Order::find($id);

        return $order;
    }

    public function getAll()
    {
        $orders = Order::all();

        return $orders;
    }

    public function update($id, $data)
    {
        $order = Order::find($id);

        if (!$order) {
            return false;
        }

        $updated = $order->update($data);

        return $updated;
    }

    public function delete($id)
    {
        $order = Order::find($id);

        if (!$order) {
            return false;
        }

        if ($order->status == StatusHelper::DELETED) {
            return true;
        }

        $deleted = $order->update([
            'deleted_at' => Carbon::now()->toDateTimeString(),
            'status' => StatusHelper::DELETED,
            'name' => StatusHelper::flagDelete($order->name),
            'code' => StatusHelper::flagDelete($order->code)
        ]);

        return $deleted;
    }

    public function destroy($id)
    {
        $order = Order::find($id);

        if (!$order) {
            return false;
        }

        $destroyed = $order->delete();

        return $destroyed;
    }

    public function isDeleted($id)
    {
        $order = Order::find($id);

        if (!$order) {
            return true;
        }

        if ($order->status != StatusHelper::DELETED) {
            return false;
        }

        return true;
    }

    public function getAllBuyersOrderHistory($filter)
    {
        $buyerStatusSql = "";
        $sellerStatusSql = "";
        $buyerUserIdSql = "";

        if (isset($filter['buyer_status'])) {
            $buyerStatus = $filter['buyer_status'];
            $buyerStatusSql = " AND transactions.buyer_status = '{$buyerStatus}' ";
        }

        if (isset($filter['seller_status'])) {
            $sellerStatus = $filter['seller_status'];
            $sellerStatusSql = " AND transactions.seller_status = '{$sellerStatus}' ";
        }

        if (isset($filter['buyer_user_id'])) {
            $userId = $filter['buyer_user_id'];
            $buyerUserIdSql = " AND orders.buyer_user_id = '{$userId}' ";
        }

        $sql = "SELECT 
                transactions.*,
                orders.address,
                (SELECT GROUP_CONCAT(CONCAT(transaction_items.product_name,'(',ROUND(transaction_items.quantity),')')) FROM transaction_items WHERE transaction_items.transaction_id = transactions.id) as 'items_string'
                FROM transactions 
                INNER JOIN orders 
                ON orders.id = transactions.order_id
                {$buyerStatusSql}
                {$sellerStatusSql}
                {$buyerUserIdSql}";

        $orderHistory = DB::select($sql);

        return $orderHistory;

    }
}