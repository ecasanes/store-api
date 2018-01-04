<?php

namespace App\Mercury\Api\Middleware;

use App\Mercury\Helpers\Rest;
use App\Mercury\Helpers\StatusHelper;
use App\Mercury\Services\CompanyService;
use App\Mercury\Services\RoleService;
use Closure;

class CompanyStaffSaleMiddleware
{

    protected $roleService;
    protected $companyService;

    public function __construct(RoleService $roleService, CompanyService $companyService)
    {
        $this->roleService = $roleService;
        $this->companyService = $companyService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $hasCompanyStaffSalePrivileges = false;
        $hasCoordinatorPrivileges = false;
        $hasFranchiseePrivileges = false;

        $staffBaseRole = StatusHelper::STAFF;
        $baseRole = StatusHelper::COMPANY_STAFF;
        $basePermission = StatusHelper::PERMISSION_SALE;

        $role = $request->attributes->get('role');
        $permissions = $request->attributes->get('permissions');
        $userId = $request->attributes->get('userId');

        $acceptedRoles = $this->roleService->getHighRankingRoles($baseRole, false);

        if ($role == $baseRole && in_array($basePermission, $permissions)) {
            $hasCompanyStaffSalePrivileges = true;
        }

        if (in_array($role, $acceptedRoles)) {
            $hasCompanyStaffSalePrivileges = true;
        }

        if($hasCompanyStaffSalePrivileges){
            return $next($request);
        }

        $staff = $this->companyService->findStaffByUserId($userId);

        if (!$staff) {
            return Rest::failed("You don't have enough permission to access this functionality. You are not registered as a staff.", [
                'role' => $role,
                'baseRole' => $baseRole,
                'basePermission' => $basePermission,
                'permissions' => json_encode($permissions),
                'hasCompanyStaffSale' => $hasCompanyStaffSalePrivileges
            ]);
        }

        $staffId = $staff->id;
        $branchId = $staff->branch_id;

        $request->attributes->add([
            'staffId' => $staffId
        ]);

        if ($hasCompanyStaffSalePrivileges) {
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
