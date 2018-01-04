<?php namespace App\Mercury\Api\Controllers;

use App\Mercury\Helpers\Rest;
use App\Mercury\Helpers\StatusHelper;
use App\Mercury\Services\PriceRuleService;
use App\Mercury\Services\ExportService;
use Illuminate\Http\Request;

class PriceRuleController extends Controller
{
    protected $priceRuleService;
    protected $exportService;

    public function __construct(Request $request, PriceRuleService $priceRuleService, ExportService $exportService)
    {
        parent::__construct($request);
        $this->payload  = $request;
        $this->priceRuleService = $priceRuleService;
        $this->exportService = $exportService;
    }

    public function getAll()
    {
        $payload = $this->payload;
        $data = $payload->all();
        $priceRulesMeta = $this->priceRuleService->getFilterMeta($data);
        $pricingList = $this->priceRuleService->filter($data);

        return Rest::success($pricingList, $priceRulesMeta);
    }

    public function get()
    {
        $payload = $this->payload;
        $id = $payload->id;

        $pricing = $this->priceRuleService->get($id);

        return Rest::success($pricing);
    }

    public function checkIfActive()
    {
        $deletedFlag = StatusHelper::DELETED;
        $payload = $this->payload;
        $code = $payload->code;
        $check = true;

        $pricing = $this->priceRuleService->findByCode($code);

        if(!$pricing || empty($pricing)) {
            $pricing = null;
        }

        if(!$pricing || $pricing && $pricing->status == $deletedFlag) {
            $check = false;
        }

        $data = [
            'result' => $pricing,
            'isActive' => $check
        ];

        return Rest::success($data);

    }

    public function create()
    {
        $payload = $this->payload;
        $data = $payload->all();

        $quantityValidation = [];

        $amountValidation = [];

        if($data['type'] == 'spend-x-get-discount') {
            $amountValidation = [
                'amount' => 'required|numeric',
                'discount' => 'required'
            ];
        }

        if($data['type'] == 'buy-x-get-discount') {
            $quantityValidation = [
                'quantity' => 'required|numeric',
                'discount' => 'required',
            ];
        }

        if($data['type'] == 'no-discount') {
            $quantityValidation = [];
        }

        $validator = $this->validator($data, [
            'name' => 'required',
            'code' => 'required',
            'type' => 'required',
            'apply_to' => 'required',
        ] + $quantityValidation + $amountValidation);

        if($validator->fails()) {
            return Rest::validationFailed($validator);
        }

        $pricing = $this->priceRuleService->create($data);

        if(!$pricing) {
            return Rest::failed('Something went wrong when creating the price rule');
        }

        return Rest::success($pricing);
    }

    public function update($id)
    {
        // TODO: refactor this soon (?)

        $payload = $this->payload;
        $data = $payload->all();

        $pricing = $this->priceRuleService->get($id);

        $isDeleted = $this->priceRuleService->isDeleted($id);

        if($isDeleted || !$pricing) {
            return Rest::notFound("No record for this price rule");
        }

        $updated = $this->priceRuleService->update($id, $data);

        return Rest::updateSuccess($updated);
    }

    public function delete($id)
    {
        $deleted = $this->priceRuleService->delete($id);

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