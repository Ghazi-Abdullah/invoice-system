<?php

namespace App\Http\Middleware;

use App\Repository\Admin\Branch\BranchRepository;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BranchMiddleware
{
    public function __construct(
        private BranchRepository $branchRepository
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'غير مصرح'], 401);
        }

        $selectedBranchId = $request->header('X-Branch-Id');

        // السوبر أدمن: يقدر يفلتر على فرع محدد لو اختاره من القائمة، أو
        // يشوف تجميع كل الفروع لو ما اختار - بدون أي تحقق من صلاحية الوصول
        if ($user->isSuperAdmin()) {
            $request->attributes->set('selected_branch_id', $selectedBranchId ? (int) $selectedBranchId : null);
            $request->attributes->set('allowed_branch_ids', null);
            return $next($request);
        }

        // الفروع المسموح بها لهذا المستخدم - دايمًا، حتى بدون هيدر
        $allowedBranches = $this->branchRepository->getUserBranches($user)->pluck('id')->toArray();

        if (empty($allowedBranches)) {
            return response()->json([
                'success' => false,
                'message' => 'لا يوجد لديك أي فرع مرتبط بحسابك، تواصل مع الإدارة',
            ], 403);
        }

        if ($selectedBranchId) {
            if (!in_array((int) $selectedBranchId, $allowedBranches)) {
                return response()->json([
                    'success' => false,
                    'message' => 'ليس لديك صلاحية للوصول إلى هذا الفرع',
                ], 403);
            }
            $request->attributes->set('selected_branch_id', (int) $selectedBranchId);
        } else {
            // ما فيه هيدر؟ نقيّد على أول فرع مسموح بدل ما نسيب البيانات مفتوحة
            $request->attributes->set('selected_branch_id', $allowedBranches[0]);
        }

        // نمررها دايمًا كـ fallback يقدر أي Repository يستخدمها بـ whereIn
        $request->attributes->set('allowed_branch_ids', $allowedBranches);

        return $next($request);
    }
}