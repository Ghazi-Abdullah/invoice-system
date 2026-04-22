<?php
namespace App\Http\Controllers\Admin;

use App\Models\OtpLog;
use App\Traits\ResponseTrait;
use App\Http\Controllers\Controller;

class OtpLogController extends Controller
{
    use ResponseTrait;

    public function index()
    {
        $logs = OtpLog::with('user:id,name,email')
            ->orderByDesc('created_at')
            ->get();

        return $this->successResponse('تم جلب السجلات', $logs);
    }
}
