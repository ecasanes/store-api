<?php

namespace App\Mercury\Api\Middleware;

use App\Mercury\Helpers\Rest;
use App\Mercury\Services\CompanyService;
use App\Mercury\Services\UserService;
use Closure;

class POSPublicMiddleware
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
