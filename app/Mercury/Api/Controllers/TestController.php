<?php

namespace App\Mercury\Api\Controllers;

use App\Mercury\Services\ActivityService;
use App\Mercury\Services\UserService;
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
