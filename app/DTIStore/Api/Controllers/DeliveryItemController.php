<?php

namespace App\DTIStore\Api\Controllers;

use App\DTIStore\Helpers\Rest;
use App\DTIStore\Services\DeliveryService;
use Illuminate\Http\Request;

class DeliveryItemController extends Controller
{
    protected $deliveryService;

    public function __construct(Request $request, DeliveryService $deliveryService)
    {
        parent::__construct($request);
        $this->deliveryService = $deliveryService;
        $this->payload = $request;
    }

    public function getAll()
    {
        $deliveryItems = $this->deliveryService->getAllDeliveryItems();

        if(!$deliveryItems) {
            return Rest::notFound("No delivery items");
        }

        return $deliveryItems;
    }

    public function get($id)
    {
        $deliveryItem = $this->deliveryService->findDeliveryItem($id);

        return $deliveryItem;
    }

    public function getByDeliveryId($id)
    {
        $deliveryItem = $this->deliveryService->findDeliveryItemById($id);

        if(!$deliveryItem){
            return Rest::notFound("Delivery item not found.");
        }

        return Rest::success($deliveryItem);
    }

    public function create()
    {
        $payload = $this->payload;
        $data = $payload->all();

        $validator = $this->validator($data, [
            'product_variation_id' => 'required|int:delivery_items',
            'delivery_id' => 'required|int:delivery_items',
        ]);

        if($validator->fails()) {
            return Rest::validationFailed($validator);
        }

        $deliveryItem = $this->deliveryService->createDeliveryItem($data);

        return Rest::success($deliveryItem);

    }

    public function update($id)
    {
        $payload = $this->payload;
        $data = $payload->all();
        
        $isDeleted = $this->deliveryService->deliveryItemIsDeleted($id);

        if($isDeleted){
            return Rest::notFound("Delivery item not found.");
        }

        $updated = $this->deliveryService->updateDeliveryItem($id, $data);
        $deliveryItem = $this->deliveryService->findDeliveryItem($id);

        return Rest::updateSuccess($updated, $deliveryItem);
    }

    public function delete($id)
    {
        $deleted = $this->deliveryService->deleteDeliveryItem($id);

        return Rest::deleteSuccess($deleted);
    }
}