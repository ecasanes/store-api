<?php namespace App\Mercury\Repositories;

use App\DeliveryItem;
use App\Mercury\Helpers\SqlHelper;
use App\Mercury\Helpers\StatusHelper;
use App\Delivery;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DeliveryRepository implements DeliveryInterface
{

    public function create(array $data)
    {
        
        $delivery = Delivery::create($data);

        return $delivery;
    }

    public function find($id)
    {
        $delivery = Delivery::find($id);

        if(!$delivery){
            return $delivery;
        }

        $delivery->deliveries = DeliveryItem::where('delivery_id', $id)->where('status', StatusHelper::ACTIVE)->get();

        return $delivery;
    }

    public function getAll()
    {
        $deliveries = Delivery::whereNotIn('status', StatusHelper::DELETED)->get();

        return $deliveries;
    }

    public function filter(array $filter)
    {
        $deletedFlag = StatusHelper::DELETED;
        $activeFlag = StatusHelper::ACTIVE;
        $paginationSql = SqlHelper::getPaginationByFilter($filter);

        $sql = "SELECT 
                  deliveries.*,
                  branches.name AS branch_name,
                  branches.type as branch_type,
                  (SELECT SUM(delivery_items.`quantity`) FROM delivery_items WHERE delivery_id = deliveries.`id` AND delivery_items.status = '{$activeFlag}') AS quantity
                FROM
                  deliveries 
                  INNER JOIN branches 
                    ON branches.`id` = deliveries.`branch_id` 
                  WHERE deliveries.status NOT IN('{$deletedFlag}')
                  ORDER BY deliveries.id DESC
                  {$paginationSql}
                ";

        $deliveries = DB::select($sql);

        return $deliveries;
    }

    public function update($id, $data)
    {
        $delivery = Delivery::find($id);

        if (!$delivery) {
            return false;
        }

        $updated = $delivery->update($data);

        return $updated;
    }

    public function delete($id)
    {
        $delivery = Delivery::find($id);

        if (!$delivery) {
            return false;
        }

        if ($delivery->status == StatusHelper::DELETED) {
            return true;
        }

        $deleted = $delivery->update([
            'deleted_at' => Carbon::now()->toDateTimeString(),
            'status' => StatusHelper::DELETED,
            'code' => StatusHelper::flagDelete($delivery->code)
        ]);

        return $deleted;
    }

    public function destroy($id)
    {
        $delivery = Delivery::find($id);

        if (!$delivery) {
            return false;
        }

        $destroyed = $delivery->delete();

        return $destroyed;
    }

    public function isDeleted($id)
    {
        $delivery = Delivery::find($id);

        if (!$delivery) {
            return true;
        }

        if ($delivery->status != StatusHelper::DELETED) {
            return false;
        }

        return true;
    }

    public function getCountAll()
    {
        // TODO: Implement getCountAll() method.
    }

    public function getCountByFilter(array $filter)
    {
        $deletedFlag = StatusHelper::DELETED;

        $sql = "SELECT 
                  count(*) as delivery_count
                FROM
                  deliveries 
                  INNER JOIN branches 
                    ON branches.`id` = deliveries.`branch_id` 
                  WHERE deliveries.status NOT IN('{$deletedFlag}')
                
                ";

        $count = DB::select($sql)[0]->delivery_count;

        return $count;
    }

    public function getFilterMeta($filter)
    {
        $limit = SqlHelper::getLimitByFilter($filter);
        $page = SqlHelper::getPageByFilter($filter);

        return [
                'length' => $this->getCountByFilter($filter),
                'limit' => $limit,
                'page' => $page
            ];
    }

    public function updateStatus($id, $status)
    {
        $updated = $this->update($id, [
            'status' => $status
        ]);

        return $updated;
    }
}