<?php namespace App\Mercury\Repositories;

use App\Branch;
use App\Mercury\Helpers\StatusHelper;
use App\Company;
use Carbon\Carbon;

class CompanyRepository implements CompanyInterface {

    public function create(array $data)
    {
        $company = Company::create($data);

        return $company;
    }

    public function find($id)
    {
        $company = Company::find($id);

        return $company;
    }

    public function getAll()
    {
        $companys = Company::whereIn('status', [StatusHelper::ACTIVE])->get();

        return $companys;
    }

    public function filter(array $filter)
    {
        // TODO: filters
        $companys = $this->getAll();

        return $companys;
    }

    public function update($id, $data)
    {
        $company = Company::find($id);

        if(!$company){
            return false;
        }

        $updated = $company->update($data);

        return $updated;
    }

    public function delete($id)
    {
        $company = Company::find($id);

        if(!$company){
            return false;
        }

        if($company->status == StatusHelper::DELETED){
            return true;
        }

        $deleted = $company->update([
            'deleted_at' => Carbon::now()->toDateTimeString(),
            'status' => StatusHelper::DELETED,
        ]);

        return $deleted;
    }

    public function destroy($id)
    {
        $company = Company::find($id);

        if(!$company){
            return false;
        }

        $destroyed = $company->delete();

        return $destroyed;
    }

    public function isDeleted($id)
    {
        $company = Company::find($id);

        if(!$company){
            return true;
        }

        if($company->status != StatusHelper::DELETED){
            return false;
        }

        return true;
    }

    public function getDefaultVatById($companyId)
    {
        $company = Company::find($companyId);

        if(!$company){
            return env('DEFAULT_VAT', StatusHelper::DEFAULT_VAT);
        }

        $vat = $company->default_vat;

        return $vat;
    }

    public function findByBranchId($branchId)
    {
        $branch = Branch::find($branchId);

        if(!$branch){
            return null;
        }

        $companyId = $branch->company_id;

        $company = Company::find($companyId);

        return $company;
    }

    public function getDefaultLowInventoryThresholdById($companyId)
    {
        $company = Company::find($companyId);

        if(!$company){
            return env('DEFAULT_LOW_THRESHOLD', StatusHelper::DEFAULT_LOW_THRESHOLD);
        }

        $threshold = $company->default_low_inventory_threshold;

        return $threshold;
    }
}