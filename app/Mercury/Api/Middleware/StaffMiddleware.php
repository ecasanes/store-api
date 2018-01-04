<?php

namespace App\Mercury\Api\Middleware;

use App\Mercury\Helpers\Rest;
use App\Mercury\Helpers\StatusHelper;
use App\Mercury\Services\CompanyService;
use App\Mercury\Services\RoleService;
use Closure;

class StaffMiddleware
{

    protected $companyService;
    protected $roleService;

    public function __construct(CompanyService $companyService, RoleService $roleService)
    {
        $this->companyService = $companyService;
        $this->roleService = $roleService;
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
        $baseRole = StatusHelper::STAFF;

        $roles = $request->attributes->get('roles');

        $acceptedRoles = $this->roleService->getHighRankingRoles($baseRole);

        if ( count ( array_intersect($acceptedRoles, $roles) ) <= 0 ) {
            return Rest::failed("You don't have the right permissions. You are not a staff.");
        }

        return $next($request);
    }
}
