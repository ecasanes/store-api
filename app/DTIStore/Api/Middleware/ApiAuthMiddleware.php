<?php

namespace App\DTIStore\Api\Middleware;

use App\DTIStore\Helpers\Rest;
use App\DTIStore\Helpers\StatusHelper;
use App\DTIStore\Services\RoleService;
use App\DTIStore\Services\UserService;
use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

class ApiAuthMiddleware
{

    protected $userService;
    protected $roleService;

    public function __construct(UserService $userService, RoleService $roleService)
    {
        $this->userService = $userService;
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
        $baseRole = StatusHelper::BUYER;
        $data = $request->all();

        $validator = Validator::make($data, [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return Rest::validationFailed($validator);
        }

        $credentials = $request->only('email','password');

        try{

            $token = JWTAuth::attempt($credentials);

        }catch(JWTException $e){
            return Rest::failed('Could not create token!');
        }

        if(!$token){
            return Rest::invalidCredentials();
        }

        //$user = JWTAuth::toUser($token);
        $user = Auth::user();
        $user = $this->userService->find($user->id);

        if(!$user){
            return Rest::invalidCredentials();
        }

        if($user->status != 'active'){
            return Rest::failed('User no longer active. Please contact the administrator');
        }

        $userId = $user->id;
        $storeId = $user->store_id;

        $role = $this->userService->findRoleByUserId($userId);
        $acceptedRoles = $this->roleService->getHighRankingRoles($baseRole);

        if(!in_array($role,$acceptedRoles)){
            return Rest::failed("Permission denied!");
        }

        $permissions = $this->userService->getPermissionsByUserId($userId);

        $token = JWTAuth::fromUser($user, [
            'role' => $role,
            'permissions' => $permissions,
            'store_id' => $storeId,
            'user_id' => $userId
        ]);

        $request->attributes->add([
            'user' => $user,
            'token' => $token
        ]);

        return $next($request);
    }
}
