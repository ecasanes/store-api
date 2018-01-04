<?php namespace App\Mercury\Repositories;

use App\Mercury\Helpers\StatusHelper;
use App\DeliveryItem;
use Carbon\Carbon;

class DeliveryItemRepository implements DeliveryItemInterface
{

    public function create(array $data)
    {
        $deliveryItem = DeliveryItem::create($data);

        return $deliveryItem;
    }

    public function find($id)
    {
        $deliveryItem = DeliveryItem::find($id);

        return $deliveryItem;
    }

    public function findByDeliveryId($id)
    {
        $deliveryItem = DeliveryItem::where('delivery_items.delivery_id', $id)
            ->join('product_variations','product_variations.id','=','delivery_items.product_variation_id')
            ->join('products','products.id','=','product_variations.product_id')
            ->select(
                'products.name',
                'delivery_items.*',
                'product_variations.size',
                'product_variations.metrics'
            )
            ->where('delivery_items.status', StatusHelper::ACTIVE)
            ->get();

        return $deliveryItem;
    }

    public function getAll()
    {
        $deliveryItems = DeliveryItem::whereIn('deliveryItems.status', [StatusHelper::ACTIVE])->get();

        return $deliveryItems;
    }

    public function filter(array $filter)
    {
        // TODO: filters
        $deliveryItems = $this->getAll();

        return $deliveryItems;
    }

    public function update($id, $data)
    {
        $deliveryItem = DeliveryItem::find($id);

        if(!$deliveryItem){
            return false;
        }

        $updated = $deliveryItem->update($data);

        return $updated;
    }

    public function delete($id)
    {
        $deliveryItem = DeliveryItem::find($id);

        if(!$deliveryItem){
            return false;
        }

        if($deliveryItem->status == StatusHelper::DELETED){
            return true;
        }

        $deleted = $deliveryItem->update([
            'deleted_at' => Carbon::now()->toDateTimeString(),
            'status' => StatusHelper::DELETED,
            'code' => StatusHelper::flagDelete($deliveryItem->code)
        ]);

        return $deleted;
    }

    public function deleteByDeliveryId($deliveryId)
    {
        $deliveryItems = DeliveryItem::where('delivery_id',$deliveryId)->get();

        foreach($deliveryItems as $item){
            $this->delete($item->id);
        }

        return true;
    }

    public function destroy($id)
    {
        $deliveryItem = DeliveryItem::find($id);

        if(!$deliveryItem){
            return false;
        }

        $destroyed = $deliveryItem->delete();

        return $destroyed;
    }

    public function isDeleted($id)
    {
        $deliveryItem = DeliveryItem::find($id);

        if(!$deliveryItem){
            return true;
        }

        if($deliveryItem->status != StatusHelper::DELETED){
            return false;
        }

        return true;
    }

    public function getCountAll()
    {
        // TODO: Implement getCountAll() method.
    }

    public function getFilterMeta($data)
    {
        // TODO: Implement getFilterMeta() method.
    }

    public function getByDeliveryId($deliveryId)
    {
        $deliveryItems = DeliveryItem::where('delivery_items.delivery_id', $deliveryId)
            ->where('delivery_items.deleted_at', null)
            ->join('deliveries','deliveries.id','=','delivery_items.delivery_id')
            ->select('delivery_items.*','deliveries.branch_id')
            ->get();

        return $deliveryItems;
    }
}