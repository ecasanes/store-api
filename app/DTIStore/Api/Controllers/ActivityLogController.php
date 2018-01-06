<?php

namespace App\DTIStore\Api\Controllers;

use Illuminate\Http\Request;
use App\DTIStore\Helpers\Rest;
use App\DTIStore\Services\ActivityService;

class ActivityLogController extends Controller
{
    protected $activityService;

    public function __construct(Request $request, ActivityService $activityService)
    {
        parent::__construct($request);

        $this->activityService = $activityService;
    }

    public function getAll()
    {
        $data = $this->payload->all();

        $activityLogsMeta = $this->activityService->getFilterMeta($data);
        $activityLogs = $this->activityService->filter($data);

        return Rest::success($activityLogs, $activityLogsMeta);

    }

    public function find()
    {
        $data = $this->payload->all();

        $activityLogId = $data['id'];

        $activityLog = $this->activityService->find($activityLogId);

        if(!$activityLog) {
            return Rest::notFound('Activity log does not exist');
        }

        return Rest::success($activityLog);
    }

    public function filter()
    {
        $data = $this->payload->all();

        $activityLogs = $this->activityService->filter($data);

        return Rest::success($activityLogs);
    }

    public function create()
    {
        $data = $this->payload->all();


        $validator = $this->validator($data, [
            'activity_log_type_id'  => 'required',
            'user_email'            => 'required',
            'user_id'               => 'required',
            'transaction_type'      => 'nullable',
            'branch_id'             => 'nullable',
            'role_name'             => 'nullable'

        ]);

        if($validator->fails()) {
            return Rest::validationFailed($validator);
        }

        $activityLog = $this->activityService->create($data);

        return Rest::success($activityLog);
    }

    public function getAllActivityLogTypes()
    {
        $data = $this->payload->all();

        $activityLogTypes = $this->activityService->filterActivityLogTypes($data);

        return Rest::success($activityLogTypes);
    }

}