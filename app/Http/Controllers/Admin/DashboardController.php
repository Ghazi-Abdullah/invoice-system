<?php

// app/Http/Controllers/Admin/DashboardController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    protected $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    public function dashboard(Request $request)
    {
        try {
            // الحصول على المستخدم الحالي
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'غير مصرح لك'
                ], 401);
            }

            // التحقق من صلاحية عرض الداشبورد
            if (!$this->checkPermission($user, 'view_dashboard')) {
                return response()->json([
                    'status' => false,
                    'message' => 'ليس لديك صلاحية لعرض لوحة التحكم'
                ], 403);
            }

            // جلب البيانات مع فلترة حسب الصلاحيات
            $data = $this->dashboardService->getDashboardData($user);

            // إضافة معلومات المستخدم والصلاحيات
            $data['user'] = [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_admin' => $user->admin_group_id == config('constants.SUPER_ADMIN_GROUP_ID'),
                'permissions' => $this->getUserPermissions($user)
            ];

            return response()->json([
                'status' => true,
                'data' => $data
            ]);

        } catch (\Exception $e) {
            Log::error('Dashboard error: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'خطأ في جلب بيانات الداشبورد',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * التحقق من صلاحية المستخدم
     */
    private function checkPermission($user, $permission)
    {
        // إذا كان مدير عام، لديه كل الصلاحيات
        if ($user->admin_group_id == config('constants.SUPER_ADMIN_GROUP_ID')) {
            return true;
        }

        // التحقق من الصلاحية من خلال المجموعة
        if ($user->adminGroup && $user->adminGroup->permissions) {
            return $user->adminGroup->permissions
                ->where('is_active', true)
                ->where('title', $permission)
                ->isNotEmpty();
        }

        return false;
    }

    /**
     * الحصول على صلاحيات المستخدم
     */
    private function getUserPermissions($user)
    {
        // إذا كان مدير عام، يرجع كل الصلاحيات النشطة
        if ($user->admin_group_id == config('constants.SUPER_ADMIN_GROUP_ID')) {
            return \App\Models\AdminPermission::where('is_active', true)
                ->pluck('title')
                ->toArray();
        }

        // صلاحيات المجموعة
        if ($user->adminGroup && $user->adminGroup->permissions) {
            return $user->adminGroup->permissions
                ->where('is_active', true)
                ->pluck('title')
                ->toArray();
        }

        return [];
    }
}
