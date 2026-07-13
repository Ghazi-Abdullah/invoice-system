<?php

namespace App\Repository\Admin\OtpLog;

use Illuminate\Database\Eloquent\Collection;

interface OtpLogInterface
{
    public function all(): Collection;
}
