<?php

namespace App\Mercury\Api\Middleware;

use App\Mercury\Helpers\Rest;
use App\Mercury\Helpers\StatusHelper;
use App\Mercury\Services\CompanyService;
use App\Mercury\Services\RoleService;
use App\Mercury\Services\UserService;
use Closure;

class AdminMiddleware
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
        $baseRole = StatusHelper::ADMIN;

        $role = $request->attributes->get('role');

        $acceptedRoles = $this->roleService->getHighRankingRoles($baseRole);

        if(!in_array($role, $acceptedRoles)){
            return Rest::failed("Permission denied!");
        }

        return $next($request);
    }
}
