<?php

namespace App\DTIStore\Api\Middleware;

use App\DTIStore\Helpers\Rest;
use App\DTIStore\Helpers\StatusHelper;
use App\DTIStore\Services\StoreService;
use App\DTIStore\Services\RoleService;
use App\DTIStore\Services\UserService;
use Closure;

class CompanyStaffInventoryMiddleware
{

    protected $roleService;
    protected $companyService;

    public function __construct(RoleService $roleService, StoreService $companyService)
    {
        $this->roleService = $roleService;
        $this->companyService = $companyService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $hasCompanyStaffInventoryPrivileges = false;
        $hasCoordinatorPrivileges = false;
        $hasFranchiseePrivileges = false;

        $staffBaseRole = StatusHelper::STAFF;
        $baseRole = StatusHelper::COMPANY_STAFF;
        $basePermission = StatusHelper::INVENTORY;

        $role = $request->attributes->get('role');
        $permissions = $request->attributes->get('permissions');
        $userId = $request->attributes->get('userId');

        $acceptedRoles = $this->roleService->getHighRankingRoles($baseRole, false);

        if ($role == $baseRole && in_array($basePermission, $permissions)) {
            $hasCompanyStaffInventoryPrivileges = true;
        }

        if (in_array($role, $acceptedRoles)) {
            $hasCompanyStaffInventoryPrivileges = true;
        }

        if($hasCompanyStaffInventoryPrivileges){
            return $next($request);
        }

        $staff = $this->companyService->findStaffByUserId($userId);

        if (!$staff) {
            return Rest::failed("You don't have enough permission to access this functionality. You are not registered as a staff.");
        }

        $staffId = $staff->id;
        $branchId = $staff->branch_id;

        $request->attributes->add([
            'staffId' => $staffId
        ]);

        if ($hasCompanyStaffInventoryPrivileges) {
            return $next($request);
        }

        $canVoid = $this->companyService->canVoid($userId);

        if ($canVoid) {
            $hasCoordinatorPrivileges = true;
        }

        if ($hasCoordinatorPrivileges) {
            return $next($request);
        }

        $isFranchisee = $this->companyService->isBranchFranchisee($branchId);

        if ($isFranchisee) {
            $hasFranchiseePrivileges = true;
        }

        if ($hasFranchiseePrivileges) {
            return $next($request);
        }

        return Rest::failed("You don't have enough permission to access this functionality.");
    }
}
