<?php namespace App\Mercury\Services;

use App\Mercury\Repositories\DeliveryInterface;
use App\Mercury\Repositories\DeliveryItemInterface;

class DeliveryService
{
    protected $delivery;
    protected $deliveryItem;

    public function __construct(DeliveryInterface $delivery, DeliveryItemInterface $deliveryItem)
    {
        $this->delivery = $delivery;
        $this->deliveryItem = $deliveryItem;
    }

    //==================================================================
    //
    // DELIVERY SECTION
    //
    //==================================================================

    public function getAll()
    {
        $deliveries = $this->delivery->getAll();

        return $deliveries;
    }

    public function filter($data)
    {

        $deliveries = $this->delivery->filter($data);

        return $deliveries;

    }

    public function getFilterMeta($data)
    {
        $filterMeta = $this->delivery->getFilterMeta($data);

        return $filterMeta;
    }

    public function find($id)
    {
        $delivery = $this->delivery->find($id);

        return $delivery;
    }

    public function create(array $data)
    {
        $delivery = $this->delivery->create($data);

        return $delivery;
    }

    public function update($id, $data)
    {
        $updated = $this->delivery->update($id, $data);

        return $updated;
    }

    public function delete($id)
    {
        $deleted = $this->delivery->delete($id);

        return $deleted;
    }

    public function isDeleted($id)
    {
        $isDeleted = $this->delivery->isDeleted($id);

        return $isDeleted;
    }

    //==================================================================
    //
    // DELIVERY ITEMS SECTION
    //
    //==================================================================

    public function getAllDeliveryItems()
    {
        $deliveryItems = $this->deliveryItem->getAll();

        return $deliveryItems;
    }

    public function findDeliveryItem($id)
    {
        $deliveryItem = $this->deliveryItem->find($id);

        return $deliveryItem;
    }

    public function findDeliveryItemById($id)
    {
        $deliveryItem = $this->deliveryItem->findByDeliveryId($id);

        return $deliveryItem;
    }

    public function createDeliveryItem(array $data)
    {
        $deliveryItem = $this->deliveryItem->create($data);

        return $deliveryItem;
    }

    public function updateDeliveryItem($id, $data)
    {
        $updated = $this->deliveryItem->update($id, $data);

        return $updated;
    }

    public function deleteDeliveryItem($id)
    {
        $deleted = $this->deliveryItem->delete($id);

        return $deleted;
    }

    public function deliveryItemIsDeleted($id)
    {
        $isDeleted = $this->deliveryItem->isDeleted($id);

        return $isDeleted;
    }
}