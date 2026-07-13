<?php

namespace App\Http\Controllers\Admin;

use App\Traits\ResponseTrait;
use App\Http\Controllers\Controller;
use App\Repository\Admin\ActivityLog\ActivityLogInterface;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    use ResponseTrait;

    protected ActivityLogInterface $activityLogRepository;

    public function __construct(ActivityLogInterface $activityLogRepository)
    {
        $this->activityLogRepository = $activityLogRepository;
    }

    public function index(Request $request)
    {
        $logs = $this->activityLogRepository->paginate($request);

        return $this->successResponse(
            __('messages.activity_logs_fetched'),
            $logs
        );
    }
}
