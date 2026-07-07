<?php
namespace App\Http\Controllers\Admin;

use App\Repository\Admin\OtpLog\OtpLogInterface;
use App\Traits\ResponseTrait;
use App\Http\Controllers\Controller;

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
        $logs = $this->otpLogRepository->all();

        return $this->successResponse('تم جلب السجلات', $logs);
    }
}
