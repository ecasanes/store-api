<?php namespace App\Mercury\Services\Traits\Product;

use App\Mercury\Helpers\StatusHelper;
use Carbon\Carbon;
use Exception;

trait DeliveryTrait{

    public function validateStocksDelivery($deliveries)
    {
        $deliveryValidations = [];

        foreach($deliveries as $delivery){

            $productVariationId = $delivery['product_variation_id'];
            $quantity = $delivery['quantity'];

            $isValid = $this->isVariationHasEnoughStocks($productVariationId, $quantity);

            $delivery['valid'] = $isValid;

            $deliveryValidations[] = $delivery;
        }

        return $deliveryValidations;

    }

    public function subtractStocksByDeliveries($deliveries)
    {
        $deliveriesWithStatus = [];

        foreach($deliveries as $delivery){

            $productVariationId = $delivery['product_variation_id'];
            $quantity = $delivery['quantity'];

            try{
                $updated = $this->subtractStocksByVariationId($productVariationId, $quantity);
            }catch(Exception $e){
                $updated = false;
            }

            $delivery['valid'] = $updated;
            $deliveriesWithStatus[] = $delivery;

        }

        return $deliveriesWithStatus;
    }

    public function voidDelivery($deliveryId)
    {
        $updated = $this->delivery->update($deliveryId, [
            'status' => StatusHelper::VOID
        ]);

        return $updated;
    }

    public function createDelivery($branchId, $status = StatusHelper::PENDING, $remarks = "", $deliveryDate = null, $invoiceNo = null)
    {
        if(empty($deliveryDate)){
            $deliveryDate = Carbon::now()->toDateTimeString();
        }

        $delivery = $this->delivery->create([
            'branch_id' => $branchId,
            'delivery_date' => $deliveryDate,
            'status' => $status,
            'remarks' => $remarks,
            'invoice_no' => $invoiceNo
        ]);

        return $delivery;
    }

    public function updateDelivery($deliveryId, $branchId, $status = StatusHelper::PENDING, $deliveryDate = null)
    {
        if(empty($deliveryDate)){
            $deliveryDate = Carbon::now()->toDateTimeString();
        }

        $delivery = $this->delivery->update($deliveryId, [
            'branch_id' => $branchId,
            'delivery_date' => $deliveryDate,
            'status' => $status
        ]);

        return $delivery;
    }

    public function createDeliveryItems($deliveryId, $deliveries)
    {
        $deliveryItems = [];

        foreach($deliveries as $delivery){

            $productVariationId = $delivery['product_variation_id'];
            $quantity = $delivery['quantity'];


            $added = $this->deliveryItem->create([
                'delivery_id' => $deliveryId,
                'product_variation_id' => $productVariationId,
                'quantity' => $quantity
            ]);

            $delivery['valid'] = $added;

            $deliveryItems[] = $delivery;

        }

        return $deliveryItems;
    }

    public function deleteDeliveryItemsByDeliveryId($deliveryId)
    {
        $deleted = $this->deliveryItem->deleteByDeliveryId($deliveryId);

        return $deleted;
    }

    public function findDelivery($deliveryId)
    {
        $delivery = $this->delivery->find($deliveryId);

        return $delivery;
    }

    public function getDeliveryItemsByDeliveryId($deliveryId)
    {
        $deliveryItems = $this->deliveryItem->getByDeliveryId($deliveryId);

        return $deliveryItems;
    }

    public function updateDeliveryStatus($deliveryId, $status)
    {
        $updated = $this->delivery->updateStatus($deliveryId, $status);

        return $updated;
    }

    public function deleteDelivery($deliveryId)
    {
        $deleted = $this->delivery->delete($deliveryId);

        return $deleted;
    }

}