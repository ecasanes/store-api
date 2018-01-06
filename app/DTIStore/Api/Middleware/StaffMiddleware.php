<?php

namespace App\DTIStore\Api\Middleware;

use App\DTIStore\Helpers\Rest;
use App\DTIStore\Helpers\StatusHelper;
use App\DTIStore\Services\StoreService;
use App\DTIStore\Services\RoleService;
use Closure;

class StaffMiddleware
{

    protected $companyService;
    protected $roleService;

    public function __construct(StoreService $companyService, RoleService $roleService)
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
