<?php

namespace App\Repository\Admin\OtpLog;

use App\Models\OtpLog;
use Illuminate\Database\Eloquent\Collection;

class OtpLogRepository implements OtpLogInterface
{
    public function all(): Collection
    {
        return OtpLog::with('user:id,name,email')
            ->orderByDesc('created_at')
            ->get();
    }
}
