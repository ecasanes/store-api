<?php

namespace App\DTIStore\Api\Middleware;

use App\DTIStore\Helpers\Rest;
use App\DTIStore\Helpers\StatusHelper;
use App\DTIStore\Services\StoreService;
use App\DTIStore\Services\RoleService;
use App\DTIStore\Services\UserService;
use Closure;

class CompanyMiddleware
{

    protected $roleService;

    public function __construct(RoleService $roleService)
    {
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
        $baseRole = StatusHelper::COMPANY;

        $role = $request->attributes->get('role');

        $acceptedRoles = $this->roleService->getHighRankingRoles($baseRole);

        if(!in_array($role, $acceptedRoles)){
            return Rest::failed("Permission denied!");
        }

        return $next($request);
    }
}
