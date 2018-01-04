<?php

namespace App\Mercury\Api\Controllers;

use App\Mercury\Helpers\Rest;
use App\Mercury\Services\DeliveryService;
use App\Mercury\Services\ExportService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DeliveryController extends Controller
{
    protected $deliveryService;
    protected $exportService;

    public function __construct(Request $request, DeliveryService $deliveryService, ExportService $exportService)
    {
        parent::__construct($request);
        $this->deliveryService = $deliveryService;
        $this->exportService = $exportService;
        $this->payload = $request;
    }

    public function getAll()
    {
        $payload = $this->payload;
        $data = $payload->all();

        $deliveries = $this->deliveryService->filter($data);
        $deliveryMeta = $this->deliveryService->getFilterMeta($data);

        return Rest::success($deliveries, $deliveryMeta);
    }

    public function get($id)
    {
        $delivery = $this->deliveryService->find($id);

        if(!$delivery){
            return Rest::notFound("Delivery not found.");
        }

        return Rest::success($delivery);
    }

    public function create()
    {
        $payload = $this->payload;
        $data = $payload->all();

        $validator = $this->validator($data, [
            //'delivery_date' => 'required|date',
            'branch_id' => 'required|int',
        ]);

        if($validator->fails()) {
            return Rest::validationFailed($validator);
        }

        if(!isset($delivery['delivery_date'])){
            $data['delivery_date'] = Carbon::now()->toDateTimeString();
        }

        $delivery = $this->deliveryService->create($data);

        return Rest::success($delivery);

    }

    public function update($id)
    {
        $payload = $this->payload;
        $data = $payload->all();

        $isDeleted = $this->deliveryService->isDeleted($id);

        if($isDeleted){
            return Rest::notFound("Delivery not found.");
        }

        $updated = $this->deliveryService->update($id, $data);
        $delivery = $this->deliveryService->find($id);

        return Rest::updateSuccess($updated, $delivery);
    }

    public function delete($id)
    {
        $deleted = $this->deliveryService->delete($id);

        return Rest::deleteSuccess($deleted);
    }

    public function export()
    {
        $data = $this->payload->all();

        $export = $this->exportService->export($data);

        if(!$export) {
            return Rest::failed("Data might not exist on the database. Please try again");
        }

        $path = url('uploads/exports/'.$export);

        return Rest::success($path);
    }

}

