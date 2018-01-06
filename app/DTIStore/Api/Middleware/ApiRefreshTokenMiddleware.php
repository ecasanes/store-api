<?php

namespace App\DTIStore\Api\Middleware;

use App\DTIStore\Helpers\Rest;
use App\DTIStore\Services\UserService;
use Closure;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Facades\JWTAuth;

class ApiRefreshTokenMiddleware
{

    protected $userService;

    public function __construct(UserService $roleService)
    {
        $this->userService = $roleService;
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
        $user = null;
        $token = null;

        try {

            $user = JWTAuth::parseToken()->authenticate();
            $token = JWTAuth::fromUser($user);

        } catch (TokenExpiredException $e) {
            // If the token is expired, then it will be refreshed and added to the headers
            try {
                $token = JWTAuth::refresh();
            } catch (JWTException $e) {
                return Rest::failed('Something went wrong while refreshing token');
            }

            $user = JWTAuth::setToken($token)->toUser();

        } catch (TokenInvalidException $e) {
            return Rest::failed('Token invalid!', [], [], $e->getStatusCode());
        } catch (JWTException $e) {
            return Rest::failed('Something went wrong while fetching token', [], [], $e->getStatusCode());
        }

        $request->attributes->add([
            'user' => $user,
            'token' => $token
        ]);

        return $next($request);
    }
}
