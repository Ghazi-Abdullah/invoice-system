<?php

namespace App\Repository\Admin\ActivityLog;

use App\Constants\Constants;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class ActivityLogRepository implements ActivityLogInterface
{
    public function paginate(Request $request): LengthAwarePaginator
    {
        $query = ActivityLog::with('user')
            ->orderBy('created_at', 'desc');

        if ($request->filled('search')) {
            $query->where('description', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $perPage = $request->per_page ?? Constants::DEFAULT_PER_PAGE;

        return $query->paginate($perPage);
    }
}
