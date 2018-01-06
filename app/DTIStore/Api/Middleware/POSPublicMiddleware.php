<?php

namespace App\DTIStore\Api\Middleware;

use App\DTIStore\Helpers\Rest;
use App\DTIStore\Services\StoreService;
use App\DTIStore\Services\UserService;
use Closure;

class POSPublicMiddleware
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

        if(!isset($data['sync_key'])){
            return Rest::failed("You don't have the right permissions. Please contact the administrator.");
        }

        $key = $data['sync_key'];

        if($key != env('PUBLIC_API_KEY','public_sync')){
            return Rest::failed("You don't have the right permissions. Please contact the administrator.");
        }

        return $next($request);
    }
}
