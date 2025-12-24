<?php

namespace App\Http\Controllers\Admin;

use App\Traits\ResponseTrait;
use App\Http\Controllers\Controller;
use App\Constants\Constants;
use App\Helpers\PermissionHelper;
use App\Repository\Admin\AdminGroup\AdminGroupInterface;
use App\Http\Requests\Admin\AdminGroup\StoreAdminGroupRequest;
use App\Http\Requests\Admin\AdminGroup\UpdateAdminGroupRequest;
use App\Http\Requests\Admin\AdminGroup\UpdatePermissionsRequest;
use App\Models\AdminGroup;
use App\Models\AdminPermission;
use Illuminate\Http\Request;

class AdminGroupController extends Controller
{
    use ResponseTrait;

    public $adminGroup;

    public function __construct(AdminGroupInterface $adminGroup)
    {
        $this->adminGroup = $adminGroup;
    }

    public function index(Request $request)
    {
        if (!PermissionHelper::checkPermission(Constants::MANAGE_ADMIN_GROUPS)) {
            return $this->failureResponse(
                __('messages.no_permission'),
                null,
                Constants::RESPONSE_FORBIDDEN
            );
        }

        $data = $this->adminGroup->index($request);

        if ($data['status']) {
            return $this->successResponse(
                __('messages.admin_groups_fetched'),
                $data['data']
            );
        }

        return $this->failureResponse($data['message'], $data['data']);
    }

    public function show($id)
    {
        if (!PermissionHelper::checkPermission(Constants::MANAGE_ADMIN_GROUPS)) {
            return $this->failureResponse(
                __('messages.no_permission'),
                null,
                Constants::RESPONSE_FORBIDDEN
            );
        }

        $data = $this->adminGroup->show($id);

        if ($data['status']) {
            return $this->successResponse(
                __('messages.admin_group_fetched'),
                $data['data']
            );
        }

        return $this->failureResponse($data['message'], $data['data']);
    }

    public function store(StoreAdminGroupRequest $request)
    {
        if (!PermissionHelper::checkPermission(Constants::MANAGE_ADMIN_GROUPS)) {
            return $this->failureResponse(
                __('messages.no_permission'),
                null,
                Constants::RESPONSE_FORBIDDEN
            );
        }

        $data = $this->adminGroup->store($request);

        if ($data['status']) {
            return $this->successResponse(
                __('messages.admin_group_created'),
                $data['data'],
                Constants::RESPONSE_CREATED
            );
        }

        return $this->failureResponse($data['message'], $data['data']);
    }

    public function update(UpdateAdminGroupRequest $request, $id)
    {
        if (!PermissionHelper::checkPermission(Constants::MANAGE_ADMIN_GROUPS)) {
            return $this->failureResponse(
                __('messages.no_permission'),
                null,
                Constants::RESPONSE_FORBIDDEN
            );
        }

        $data = $this->adminGroup->show($id);

        if (!$data['status']) {
            return $this->failureResponse($data['message'], $data['data']);
        }

        $updateData = $this->adminGroup->update($request, $data['data']);

        if ($updateData['status']) {
            return $this->successResponse(
                __('messages.admin_group_updated'),
                $updateData['data']
            );
        }

        return $this->failureResponse($updateData['message'], $updateData['data']);
    }

    public function destroy($id)
    {
        if (!PermissionHelper::checkPermission(Constants::MANAGE_ADMIN_GROUPS)) {
            return $this->failureResponse(
                __('messages.no_permission'),
                null,
                Constants::RESPONSE_FORBIDDEN
            );
        }

        $data = $this->adminGroup->show($id);

        if (!$data['status']) {
            return $this->failureResponse($data['message'], $data['data']);
        }

        $deleteData = $this->adminGroup->destroy($data['data']);

        if ($deleteData['status']) {
            return $this->successResponse(
                __('messages.admin_group_deleted'),
                $deleteData['data']
            );
        }

        return $this->failureResponse($deleteData['message'], $deleteData['data']);
    }

    public function availablePermissions($id)
    {
        if (!PermissionHelper::checkPermission(Constants::MANAGE_ADMIN_GROUPS)) {
            return $this->failureResponse(
                __('messages.no_permission'),
                null,
                Constants::RESPONSE_FORBIDDEN
            );
        }

        $group = AdminGroup::with(['permissions'])->find($id);

        if (!$group) {
            return $this->failureResponse(
                __('messages.not_found'),
                null,
                Constants::RESPONSE_NOT_FOUND
            );
        }

        $permissions = AdminPermission::where('is_active', true)
            ->orderBy('title')
            ->get(['id', 'title', 'description_en', 'description_ar', 'is_parent', 'admin_menu_id', 'admin_sub_menu_id']);

        $groupPermissionIds = $group->permissions->pluck('id')->toArray();

        return $this->successResponse(
            __('messages.permissions_fetched'),
            [
                'permissions' => $permissions,
                'selected_permissions' => $groupPermissionIds
            ]
        );
    }

    public function updatePermissions(UpdatePermissionsRequest $request, $id)
    {
        if (!PermissionHelper::checkPermission(Constants::MANAGE_ADMIN_GROUPS)) {
            return $this->failureResponse(
                __('messages.no_permission'),
                null,
                Constants::RESPONSE_FORBIDDEN
            );
        }

        $group = AdminGroup::find($id);

        if (!$group) {
            return $this->failureResponse(
                __('messages.not_found'),
                null,
                Constants::RESPONSE_NOT_FOUND
            );
        }

        if ($group->id == Constants::SUPER_ADMIN_GROUP_ID) {
            return $this->failureResponse(
                __('messages.cannot_modify_super_admin'),
                null,
                Constants::RESPONSE_FORBIDDEN
            );
        }

        $group->permissions()->sync($request->permissions);

        return $this->successResponse(
            __('messages.permissions_updated'),
            [
                'group' => $group->load('permissions')
            ]
        );
    }

    public function simpleList()
    {
        $groups = AdminGroup::where('is_active', true)
            ->orderBy('title_en')
            ->get(['id', 'title_en', 'title_ar']);

        return $this->successResponse(
            __('messages.admin_groups_fetched'),
            $groups
        );
    }

    public function groupsWithPermissions()
    {
        if (!PermissionHelper::checkPermission(Constants::MANAGE_ADMIN_GROUPS)) {
            return $this->failureResponse(
                __('messages.no_permission'),
                null,
                Constants::RESPONSE_FORBIDDEN
            );
        }

        $groups = AdminGroup::with(['permissions'])
            ->orderBy('id')
            ->get();

        return $this->successResponse(
            __('messages.groups_with_permissions_fetched'),
            $groups
        );
    }
}
