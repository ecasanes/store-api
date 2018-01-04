<?php

namespace App\Mercury\Api\Middleware;

use App\Mercury\Helpers\Rest;
use App\Mercury\Services\CompanyService;
use App\Mercury\Services\UserService;
use Closure;

class POSPostMiddleware
{

    protected $companyService;
    protected $userService;

    public function __construct(CompanyService $companyService, UserService $userService)
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
        $staffId = null;
        $hasMultipleAccess = 0;

        $data = $request->all();

        if(!isset($data['key'])){
            return Rest::failed("You don't have the right permissions. Please double check if you've entered the Branch Key in the settings page.");
        }

        if(!isset($data['staff_id'])){
            return Rest::failed("You don't have the right permissions. Please input a valid Staff ID");
        }

        $key = $data['key'];
        $staffId = $data['staff_id'];

        $staff = $this->companyService->findUserByStaffId($staffId);

        if(!$staff){
            return Rest::failed("You don't have the right permissions. Please input a valid Staff ID");
        }

        $hasMultipleAccess = $staff->has_multiple_access;

        $branch = $this->companyService->findBranchByKey($key);

        if(!$branch && !$hasMultipleAccess){
            return Rest::failed("You don't have the right permissions. Please double check if you've entered Branch Key in the settings page.");
        }

        $user = $this->companyService->findUserByKeyAndStaffId($key, $staffId);

        if(!$user && !$hasMultipleAccess){
            return Rest::notFound("You don't have the right permissions. You are not assigned to this branch.");
        }

        if($hasMultipleAccess){
            $user = $staff;
        }

        $userId = $user->id;
        $role = $this->userService->findRoleByUserId($userId);
        $roles = $this->userService->getRolesByUserId($userId);
        $permissions = $this->userService->getPermissionsByUserId($userId);

        $request->attributes->add([
            'user' => $user,
            'user_id' => $userId,
            'role' => $role,
            'roles' => $roles,
            'permissions' => $permissions
        ]);

        return $next($request);
    }
}
