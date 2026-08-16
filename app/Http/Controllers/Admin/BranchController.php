<?php

namespace App\Http\Controllers\Admin;

use App\Traits\ResponseTrait;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Branch\StoreBranchRequest;
use App\Http\Requests\Admin\Branch\UpdateBranchRequest;
use App\Repository\Admin\Branch\BranchRepository;
use App\Constants\Constants;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BranchController extends Controller
{
    use ResponseTrait;

    public function __construct(
        private BranchRepository $branchRepository
    ) {}

    public function index(Request $request)
    {
        try {
            $filters = $request->only(['search', 'is_active']);
            $branches = $this->branchRepository->getAll($filters);

            return $this->successResponse(
                __('messages.branches_fetched'),
                $branches
            );
        } catch (\Exception $e) {
            Log::error('Branches index error: ' . $e->getMessage());
            return $this->failureResponse(
                __('messages.operation_failed'),
                null,
                Constants::RESPONSE_SERVER_ERROR
            );
        }
    }

    public function active()
    {
        try {
            $branches = $this->branchRepository->getActive();

            return $this->successResponse(
                __('messages.branches_fetched'),
                $branches
            );
        } catch (\Exception $e) {
            Log::error('Branches active error: ' . $e->getMessage());
            return $this->failureResponse(
                __('messages.operation_failed'),
                null,
                Constants::RESPONSE_SERVER_ERROR
            );
        }
    }

    public function myBranches(Request $request)
    {
        try {
            $user = $request->user();
            $branches = $this->branchRepository->getUserBranches($user);

            return $this->successResponse(
                __('messages.branches_fetched'),
                [
                    'branches'       => $branches,
                    'default_branch' => $this->branchRepository->getUserDefaultBranch($user)?->id,
                    'is_super_admin' => $user->isSuperAdmin(),
                ]
            );
        } catch (\Exception $e) {
            Log::error('My branches error: ' . $e->getMessage());
            return $this->failureResponse(
                __('messages.operation_failed'),
                null,
                Constants::RESPONSE_SERVER_ERROR
            );
        }
    }

    public function store(StoreBranchRequest $request)
    {
        try {
            $branch = $this->branchRepository->create($request->validated());

            return $this->successResponse(
                __('messages.branch_created'),
                $branch,
                Constants::RESPONSE_CREATED
            );
        } catch (\Exception $e) {
            Log::error('Branch store error: ' . $e->getMessage());
            return $this->failureResponse(
                __('messages.operation_failed'),
                null,
                Constants::RESPONSE_SERVER_ERROR
            );
        }
    }

    public function show($id)
    {
        try {
            $branch = $this->branchRepository->findById((int) $id);

            if (!$branch) {
                return $this->failureResponse(
                    __('messages.branch_not_found'),
                    null,
                    Constants::RESPONSE_NOT_FOUND
                );
            }

            return $this->successResponse(
                __('messages.branch_fetched'),
                $branch
            );
        } catch (\Exception $e) {
            Log::error('Branch show error: ' . $e->getMessage());
            return $this->failureResponse(
                __('messages.operation_failed'),
                null,
                Constants::RESPONSE_SERVER_ERROR
            );
        }
    }

    public function update(UpdateBranchRequest $request, $id)
    {
        try {
            $branch = $this->branchRepository->findById((int) $id);

            if (!$branch) {
                return $this->failureResponse(
                    __('messages.branch_not_found'),
                    null,
                    Constants::RESPONSE_NOT_FOUND
                );
            }

            $branch = $this->branchRepository->update($branch, $request->validated());

            return $this->successResponse(
                __('messages.branch_updated'),
                $branch
            );
        } catch (\Exception $e) {
            Log::error('Branch update error: ' . $e->getMessage());
            return $this->failureResponse(
                __('messages.operation_failed'),
                null,
                Constants::RESPONSE_SERVER_ERROR
            );
        }
    }

    public function destroy($id)
    {
        try {
            $branch = $this->branchRepository->findById((int) $id);

            if (!$branch) {
                return $this->failureResponse(
                    __('messages.branch_not_found'),
                    null,
                    Constants::RESPONSE_NOT_FOUND
                );
            }

            $this->branchRepository->delete($branch);

            return $this->successResponse(
                __('messages.branch_deleted'),
                null
            );
        } catch (\Exception $e) {
            Log::error('Branch destroy error: ' . $e->getMessage());
            return $this->failureResponse(
                __('messages.operation_failed'),
                null,
                Constants::RESPONSE_SERVER_ERROR
            );
        }
    }

    public function assignBranches(Request $request)
    {
        if (!\App\Helpers\PermissionHelper::checkPermission(Constants::MANAGE_BRANCHES)) {             return $this->failureResponse(                 __('messages.no_permission'),                 null,                 Constants::RESPONSE_FORBIDDEN             );         } 
        try {
            $validated = $request->validate([
                'user_id'            => ['required', 'exists:users,id'],
                'branch_ids'         => ['required', 'array'],
                'branch_ids.*'       => ['exists:branches,id'],
                'default_branch_id'  => ['nullable', 'in:' . implode(',', $request->input('branch_ids', []))],
            ]);

            $user = \App\Models\User::findOrFail($validated['user_id']);

            $this->branchRepository->syncUserBranches(
                $user,
                $validated['branch_ids'],
                $validated['default_branch_id'] ?? null
            );

            return $this->successResponse(
                __('messages.branches_assigned'),
                null
            );
        } catch (\Exception $e) {
            Log::error('Assign branches error: ' . $e->getMessage());
            return $this->failureResponse(
                __('messages.operation_failed'),
                null,
                Constants::RESPONSE_SERVER_ERROR
            );
        }
    }
}