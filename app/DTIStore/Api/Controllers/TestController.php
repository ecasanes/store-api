<?php

namespace App\DTIStore\Api\Controllers;

use App\DTIStore\Services\ActivityService;
use App\DTIStore\Services\UserService;
use Illuminate\Http\Request;

class TestController extends Controller
{
    protected $userService;
    protected $activityService;

    public function __construct(Request $request, UserService $userService, ActivityService $activityService)
    {
        parent::__construct($request);

        $this->userService = $userService;

        $this->activityService = $activityService;
    }

    public function index()
    {

    }
}
