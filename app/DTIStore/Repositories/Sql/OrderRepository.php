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

    public function getOrderTransactions($filter)
    {
        $buyerStatusSql = "";
        $sellerStatusSql = "";
        $buyerUserIdSql = "";
        $sellerUserIdSelectSql = "";

        if (isset($filter['buyer_status'])) {
            $buyerStatus = $filter['buyer_status'];
            $buyerStatusSql = " AND transactions.buyer_status = '{$buyerStatus}' ";
        }

        if (isset($filter['seller_status'])) {
            $sellerStatus = $filter['seller_status'];
            $sellerStatusSql = " AND transactions.seller_status = '{$sellerStatus}' ";
        }

        if (isset($filter['buyer_user_id'])) {
            $buyerUserId = $filter['buyer_user_id'];
            $buyerUserIdSql = " AND orders.buyer_user_id = '{$buyerUserId}' ";
        }

        if (isset($filter['seller_user_id'])) {
            $sellerUserId = $filter['seller_user_id'];
            $sellerUserIdSelectSql = " AND user_stores.user_id = '{$sellerUserId}' ";
        }

        $sql = "SELECT 
                transactions.*,
                transactions.id as 'transaction_id',
                orders.address,
                orders.buyer_user_id,
                CONCAT(COALESCE(users.firstname,''),' ',COALESCE(users.middlename,''),' ',COALESCE(users.lastname,'')) as 'seller_name',
                (SELECT CONCAT(COALESCE(u.firstname,''),' ',COALESCE(u.middlename,''),' ',COALESCE(u.lastname,'')) FROM users u WHERE u.id = orders.buyer_user_id) as 'buyer_name',
                (SELECT GROUP_CONCAT(CONCAT(transaction_items.product_name,'(',ROUND(transaction_items.quantity),')')) FROM transaction_items WHERE transaction_items.transaction_id = transactions.id) as 'items_string'
                FROM transactions 
                INNER JOIN orders 
                ON orders.id = transactions.order_id
                LEFT JOIN stores
                ON transactions.store_id = stores.id
                LEFT JOIN user_stores
                ON user_stores.store_id = transactions.store_id
                LEFT JOIN users
                ON users.id = user_stores.user_id
                WHERE transactions.id IS NOT NULL
                {$buyerStatusSql}
                {$sellerStatusSql}
                {$buyerUserIdSql}
                {$sellerUserIdSelectSql}
                ORDER BY transactions.updated_at DESC
                ";

        $orderHistory = DB::select($sql);

        return $orderHistory;

    }
}