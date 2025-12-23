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
        if (!PermissionHelper::checkPermission(Constants::VIEW_ADMIN_GROUPS)) {
            return $this->failureResponse(__('messages.no_permission'), null, 403);
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
        if (!PermissionHelper::checkPermission(Constants::VIEW_ADMIN_GROUPS)) {
            return $this->failureResponse(__('messages.no_permission'), null, 403);
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
        if (!PermissionHelper::checkPermission(Constants::CREATE_ADMIN_GROUP)) {
            return $this->failureResponse(__('messages.no_permission'), null, 403);
        }

        $data = $this->adminGroup->store($request);

        if ($data['status']) {
            return $this->successResponse($data['message'], $data['data'], 201);
        }

        return $this->failureResponse($data['message'], $data['data']);
    }

    public function update(UpdateAdminGroupRequest $request, $id)
    {
        if (!PermissionHelper::checkPermission(Constants::EDIT_ADMIN_GROUP)) {
            return $this->failureResponse(__('messages.no_permission'), null, 403);
        }

        $data = $this->adminGroup->show($id);

        if (!$data['status']) {
            return $this->failureResponse($data['message'], $data['data']);
        }

        $updateData = $this->adminGroup->update($request, $data['data']);

        if ($updateData['status']) {
            return $this->successResponse($updateData['message'], $updateData['data']);
        }

        return $this->failureResponse($updateData['message'], $updateData['data']);
    }

    public function destroy($id)
    {
        if (!PermissionHelper::checkPermission(Constants::DELETE_ADMIN_GROUP)) {
            return $this->failureResponse(__('messages.no_permission'), null, 403);
        }

        $data = $this->adminGroup->show($id);

        if (!$data['status']) {
            return $this->failureResponse($data['message'], $data['data']);
        }

        $deleteData = $this->adminGroup->destroy($data['data']);

        if ($deleteData['status']) {
            return $this->successResponse($deleteData['message'], $deleteData['data']);
        }

        return $this->failureResponse($deleteData['message'], $deleteData['data']);
    }

    public function permissions($id)
    {
        if (!PermissionHelper::checkPermission(Constants::MANAGE_PERMISSIONS)) {
            return $this->failureResponse(__('messages.no_permission'), null, 403);
        }

        $data = $this->adminGroup->show($id);

        if (!$data['status']) {
            return $this->failureResponse($data['message'], $data['data']);
        }

        $permissionsData = $this->adminGroup->getPermissions($data['data']);

        if ($permissionsData['status']) {
            return $this->successResponse($permissionsData['message'], $permissionsData['data']);
        }

        return $this->failureResponse($permissionsData['message'], $permissionsData['data']);
    }

    public function updatePermissions(UpdatePermissionsRequest $request, $id)
    {
        if (!PermissionHelper::checkPermission(Constants::MANAGE_PERMISSIONS)) {
            return $this->failureResponse(__('messages.no_permission'), null, 403);
        }

        $data = $this->adminGroup->show($id);

        if (!$data['status']) {
            return $this->failureResponse($data['message'], $data['data']);
        }

        $updateData = $this->adminGroup->updatePermissions($data['data'], $request->permissions);

        if ($updateData['status']) {
            return $this->successResponse($updateData['message'], $updateData['data']);
        }

        return $this->failureResponse($updateData['message'], $updateData['data']);
    }

    public function availablePermissions()
    {
        if (!PermissionHelper::checkPermission(Constants::MANAGE_PERMISSIONS)) {
            return $this->failureResponse(__('messages.no_permission'), null, 403);
        }

        $data = $this->adminGroup->getAvailablePermissions();

        if ($data['status']) {
            return $this->successResponse($data['message'], $data['data']);
        }

        return $this->failureResponse($data['message'], $data['data']);
    }

    public function groupsWithPermissions()
    {
        if (!PermissionHelper::checkPermission(Constants::VIEW_ADMIN_GROUPS)) {
            return $this->failureResponse(__('messages.no_permission'), null, 403);
        }

        $data = $this->adminGroup->getGroupsWithPermissions();

        if ($data['status']) {
            return $this->successResponse($data['message'], $data['data']);
        }

        return $this->failureResponse($data['message'], $data['data']);
    }

    // أضف هذه الدالة الجديدة
    public function simpleList()
    {
        if (!PermissionHelper::checkPermission(Constants::VIEW_ADMIN_GROUPS)) {
            return $this->failureResponse(__('messages.no_permission'), null, 403);
        }

        $data = $this->adminGroup->getSimpleList();

        if ($data['status']) {
            return $this->successResponse($data['message'], $data['data']);
        }

        return $this->failureResponse($data['message'], $data['data']);
    }
}
