<?php

namespace App\DTIStore\Api\Middleware;

use App\DTIStore\Helpers\Rest;
use App\DTIStore\Services\StoreService;
use App\DTIStore\Services\UserService;
use Closure;

class POSViewMiddleware
{

    protected $companyService;
    protected $userService;

    public function __construct(StoreService $companyService, UserService $userService)
    {
        $this->companyService = $companyService;
        $this->userService = $userService;
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
        $user = null;

        $data = $request->all();

        if(!isset($data['key'])){
            return Rest::failed("You don't have the right permissions. Check your settings if you've provided the right key.");
        }

        $key = $data['key'];

        $branch = $this->companyService->findBranchByKey($key);

        if(!$branch){
            return Rest::failed("You don't have the right permissions. Branch not found.");
        }

        /*

        $role = $this->userService->findRoleByUserId($userId);
        $roles = $this->userService->getRolesByUserId($userId);
        $permissions = $this->userService->getPermissionsByUserId($userId);

        $request->attributes->add([
            'user' => null,
            'user_id' => null,
            'role' => null,
            'roles' => [],
            'permissions' => []
        ]);*/

        return $next($request);
    }
}
