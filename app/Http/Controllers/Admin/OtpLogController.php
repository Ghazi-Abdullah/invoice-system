<?php

namespace App\Http\Controllers\Admin;

use App\Repository\Admin\OtpLog\OtpLogInterface;
use App\Traits\ResponseTrait;
use App\Http\Controllers\Controller;
use App\Helpers\PermissionHelper;
use App\Constants\Constants;

class OtpLogController extends Controller
{
    use ResponseTrait;

    protected OtpLogInterface $otpLogRepository;

    public function __construct(OtpLogInterface $otpLogRepository)
    {
        $this->otpLogRepository = $otpLogRepository;
    }

    public function index()
    {
        // ✅ فحص صلاحية مضاف
        if (!PermissionHelper::checkPermission(Constants::VIEW_OTP_LOGS)) {
            return $this->failureResponse(
                __('messages.no_permission'),
                null,
                Constants::RESPONSE_FORBIDDEN
            );
        }

        $logs = $this->otpLogRepository->all();
        return $this->successResponse('تم جلب السجلات', $logs);
    }
}
