<?php

namespace App\Repository\Admin\ActivityLog;

use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

interface ActivityLogInterface
{
    public function paginate(Request $request): LengthAwarePaginator;
}
