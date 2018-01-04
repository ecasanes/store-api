<?php

namespace App\Mercury\Api\Controllers;

use App\Mercury\Helpers\Rest;
use App\Mercury\Helpers\StatusHelper;
use App\Mercury\Services\CompanyService;
use App\Mercury\Services\ProductService;
use App\Mercury\Services\TransactionService;
use App\Mercury\Services\UserService;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    protected $companyService;
    protected $productService;
    protected $userService;
    protected $transactionService;

    public function __construct(Request $request, CompanyService $companyService, ProductService $productService, UserService $userService, TransactionService $transactionService)
    {
        parent::__construct($request);

        $this->companyService = $companyService;
        $this->productService = $productService;
        $this->userService = $userService;
        $this->transactionService = $transactionService;
    }

    public function getAll()
    {

        $payload = $this->payload;
        $filter = $payload->all();

        $branches = $this->companyService->getAllBranches($filter);

        return Rest::success($branches);
    }

    public function get($id)
    {
        $payload = $this->payload;
        $filter = $payload->all();

        $excludeStocks = 0;

        if(isset($filter['exclude_stocks'])){
            $excludeStocks = $filter['exclude_stocks'];
        }

        $branch = $this->companyService->findBranch($id);

        if (!$branch) {
            return Rest::notFound("Branch not found.");
        }

        if (!$excludeStocks) {
            $items = $this->productService->getBranchStocksById($id);
            $branch->items = $items;
        }

        return Rest::success($branch);
    }

    public function findByBranchKey($key)
    {
        $branch = $this->companyService->findBranchByKey($key);

        if (!$branch) {
            return Rest::notFound("Branch not found.");
        }

        return Rest::success($branch);
    }

    public function checkBranchKey($key) {

        $branch = $this->companyService->findBranchByKey($key);

        if (!$branch) {
            return Rest::failed(false);
        }

        return Rest::success(true);

    }

    public function getBranchSalesSummaryByKey($key)
    {

        $filter = [
            'keys' => $key,
            'range' => 'day'
        ];

        $branchSummary = $this->transactionService->getBranchesSalesSummary($filter);

        if(count($branchSummary)<=0){
            return Rest::notFound("Branch not found.");
        }

        $branchSummary = $branchSummary[0];

        return Rest::success($branchSummary);
    }

    public function create()
    {
        $data = $this->payload->all();

        $roleCode = StatusHelper::BRANCH;

        $validator = $this->validator($data, [
            'name' => 'required|unique:branches',
            'address' => 'required',
            'email' => 'required|unique:users',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return Rest::validationFailed($validator);
        }

        unset($data['status']);
        $branchInfo = $this->companyService->createBranch($data);

        if (!$branchInfo) {
            return Rest::failed("Something went wrong while creating branch.");
        }

        $branchId = $branchInfo->id;
        $data['role'] = $roleCode;
        $userInfo = $this->userService->create($data);

        if (!$userInfo) {
            $this->companyService->deleteBranch($branchId);
            return Rest::failed("Something went wrong while creating user associated to branch.");
        }

        $userId = $userInfo->id;
        if ($branchId && $roleCode == StatusHelper::BRANCH) {
            $this->userService->generateStaffId($userId, $branchId);
            $this->userService->update($userId, [
                'branch_id_registered' => $branchId
            ]);
        }

        $branch = $this->companyService->findBranch($branchId);

        return Rest::success($branch, [
            'user' => $userInfo,
            'branch' => $branchInfo
        ]);
    }

    public function update($id)
    {
        $data = $this->payload->all();

        $branch = $this->companyService->findBranch($id);

        if (!$branch) {
            return Rest::notFound("Branch not found");
        }

        $validator = $this->validator($data, [
            'name' => 'required|unique:branches,name,' . $id,
            'address' => 'required',
            'owner_user_id' => 'required'
        ]);

        if ($validator->fails()) {
            return Rest::validationFailed($validator);
        }

        $userId = $data['owner_user_id'];

        $userValidator = $this->validator($data, [
            'email' => 'required|unique:users,email,' . $userId,
        ]);

        if ($userValidator->fails()) {
            return Rest::validationFailed($userValidator);
        }

        unset($data['status']);
        $branchUpdated = $this->companyService->updateBranch($id, $data);

        if (!$branchUpdated) {
            return Rest::failed("Something went wrong while updating branch");
        }

        $userUpdated = $this->userService->update($userId, $data);

        return Rest::updateSuccess($userUpdated);
    }

    public function delete($id)
    {
        // when deleting branch you delete associated user
        $branch = $this->companyService->findBranch($id);

        if (!$branch) {
            return Rest::notFound("Branch not found");
        }

        $userId = $branch->owner_user_id;

        $deleted = $this->companyService->deleteBranch($id);

        if (!$deleted) {
            return Rest::failed("Something went wrong while deleting branch.");
        }

        $userDeleted = $this->userService->delete($userId);

        if (!$userDeleted) {
            return Rest::failed("Something went wrong while deleting user associated to branch");
        }

        return Rest::deleteSuccess($userDeleted);
    }

}
