<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Repository\Admin\Dashboard\DashboardInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    protected DashboardInterface $dashboardRepository;

    public function __construct(DashboardInterface $dashboardRepository)
    {
        $this->dashboardRepository = $dashboardRepository;
    }

    public function dashboard(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'غير مصرح لك'
                ], 401);
            }

            $data = $this->dashboardRepository->getDashboardData($user);

            return response()->json([
                'status' => true,
                'message' => 'تم جلب بيانات لوحة التحكم بنجاح',
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

    public function stats(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json(['status' => false, 'message' => 'غير مصرح'], 401);
            }

            $data = $this->dashboardRepository->getDashboardData($user);

            return response()->json([
                'status' => true,
                'data' => $data['stats']
            ]);

        } catch (\Exception $e) {
            Log::error('Dashboard stats error: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'خطأ في جلب الإحصائيات'
            ], 500);
        }
    }

    public function monthlyRevenue(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json(['status' => false, 'message' => 'غير مصرح'], 401);
            }

            $data = $this->dashboardRepository->getDashboardData($user);

            return response()->json([
                'status' => true,
                'data' => $data['monthlyRevenue']
            ]);

        } catch (\Exception $e) {
            Log::error('Monthly revenue error: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'خطأ في جلب الإيرادات الشهرية'
            ], 500);
        }
    }

    public function recentActivity(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json(['status' => false, 'message' => 'غير مصرح'], 401);
            }

            $data = $this->dashboardRepository->getDashboardData($user);

            return response()->json([
                'status' => true,
                'data' => $data['recentActivity']
            ]);

        } catch (\Exception $e) {
            Log::error('Recent activity error: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'خطأ في جلب النشاط الحديث'
            ], 500);
        }
    }
}
