<?php

namespace App\Mercury\Api\Controllers;

use App\Mercury\Helpers\Rest;
use App\Mercury\Services\CompanyService;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    protected $payload;
    protected $companyService;

    public function __construct(Request $request, CompanyService $companyService)
    {
        parent::__construct($request);
        $this->companyService = $companyService;
    }

    public function getAll()
    {
        $companies = $this->companyService->getAll();

        return Rest::success($companies);
    }

    public function update($id)
    {
        $payload = $this->payload;
        $data = $payload->all();

        $company = $this->companyService->find($id);

        if(!$company){
            return Rest::notFound("Company not found");
        }

        $isDeleted = $this->companyService->isDeleted($id);

        if($isDeleted){
            return Rest::notFound("Company is not active. Please contact administrator.");
        }

        $updated = $this->companyService->update($id, $data);
        $product = $this->companyService->find($id);

        return Rest::updateSuccess($updated, $product);
    }

    public function delete($id)
    {
        $deleted = $this->companyService->delete($id);

        return Rest::deleteSuccess($deleted);
    }
}
