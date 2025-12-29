<?php

namespace App\Http\Controllers\Admin;

use App\Traits\ResponseTrait;
use App\Http\Controllers\Controller;
use App\Constants\Constants;
use App\Helpers\PermissionHelper;
use App\Repository\Admin\Permission\PermissionInterface;
use App\Http\Requests\Admin\Permission\StorePermissionRequest;
use App\Http\Requests\Admin\Permission\UpdatePermissionRequest;
use App\Models\AdminPermission;
use App\Models\AdminMenu;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    use ResponseTrait;

    public $permission;

    public function __construct(PermissionInterface $permission)
    {
        $this->permission = $permission;
    }

    public function index(Request $request)
    {
        if (!PermissionHelper::checkPermission(Constants::MANAGE_PERMISSIONS)) {
            return $this->failureResponse(
                __('messages.no_permission'),
                null,
                Constants::RESPONSE_FORBIDDEN
            );
        }

        $data = $this->permission->index($request);

        if ($data['status']) {
            return $this->successResponse(
                __('messages.permissions_fetched'),
                $data['data']
            );
        }

        return $this->failureResponse($data['message'], $data['data']);
    }

    public function show($id)
    {
        if (!PermissionHelper::checkPermission(Constants::MANAGE_PERMISSIONS)) {
            return $this->failureResponse(
                __('messages.no_permission'),
                null,
                Constants::RESPONSE_FORBIDDEN
            );
        }

        $data = $this->permission->show($id);

        if ($data['status']) {
            return $this->successResponse(
                __('messages.permission_fetched'),
                $data['data']
            );
        }

        return $this->failureResponse($data['message'], $data['data']);
    }

    public function store(StorePermissionRequest $request)
    {
        if (!PermissionHelper::checkPermission(Constants::MANAGE_PERMISSIONS)) {
            return $this->failureResponse(
                __('messages.no_permission'),
                null,
                Constants::RESPONSE_FORBIDDEN
            );
        }

        $data = $this->permission->store($request);

        if ($data['status']) {
            return $this->successResponse(
                __('messages.permission_created'),
                $data['data'],
                Constants::RESPONSE_CREATED
            );
        }

        return $this->failureResponse($data['message'], $data['data']);
    }

    public function update(UpdatePermissionRequest $request, $id)
    {
        if (!PermissionHelper::checkPermission(Constants::MANAGE_PERMISSIONS)) {
            return $this->failureResponse(
                __('messages.no_permission'),
                null,
                Constants::RESPONSE_FORBIDDEN
            );
        }

        $data = $this->permission->show($id);

        if (!$data['status']) {
            return $this->failureResponse($data['message'], $data['data']);
        }

        $updateData = $this->permission->update($request, $data['data']);

        if ($updateData['status']) {
            return $this->successResponse(
                __('messages.permission_updated'),
                $updateData['data']
            );
        }

        return $this->failureResponse($updateData['message'], $updateData['data']);
    }

    public function destroy($id)
    {
        if (!PermissionHelper::checkPermission(Constants::MANAGE_PERMISSIONS)) {
            return $this->failureResponse(
                __('messages.no_permission'),
                null,
                Constants::RESPONSE_FORBIDDEN
            );
        }

        $data = $this->permission->show($id);

        if (!$data['status']) {
            return $this->failureResponse($data['message'], $data['data']);
        }

        $deleteData = $this->permission->destroy($data['data']);

        if ($deleteData['status']) {
            return $this->successResponse(
                __('messages.permission_deleted'),
                $deleteData['data']
            );
        }

        return $this->failureResponse($deleteData['message'], $deleteData['data']);
    }

    public function getAll()
    {
        if (!PermissionHelper::checkPermission(Constants::MANAGE_PERMISSIONS)) {
            return $this->failureResponse(
                __('messages.no_permission'),
                null,
                Constants::RESPONSE_FORBIDDEN
            );
        }

        $permissions = AdminPermission::where('is_active', true)
            ->orderBy('title')
            ->get(['id', 'title', 'description_en', 'description_ar', 'is_parent', 'admin_menu_id', 'admin_sub_menu_id']);

        return $this->successResponse(
            __('messages.permissions_fetched'),
            $permissions
        );
    }

    public function menus()
    {
        $menus = AdminMenu::with(['subMenus' => function ($query) {
                $query->where('is_active', 1)->orderBy('sort_order');
            }])
            ->where('is_active', 1)
            ->orderBy('sort_order')
            ->get();

        return $this->successResponse(
            __('messages.menus_fetched'),
            $menus
        );
    }

    public function permissionsWithMenus()
    {
        if (!PermissionHelper::checkPermission(Constants::MANAGE_PERMISSIONS)) {
            return $this->failureResponse(
                __('messages.no_permission'),
                null,
                Constants::RESPONSE_FORBIDDEN
            );
        }

        $data = $this->permission->getPermissionsWithMenus();

        if ($data['status']) {
            return $this->successResponse(
                __('messages.permissions_fetched'),
                $data['data']
            );
        }

        return $this->failureResponse($data['message'], $data['data']);
    }

    public function getPermissionsWithMenusInternal()
    {
        $permissions = AdminPermission::with(['menu', 'subMenu', 'parent'])
            ->where('is_active', true)
            ->orderBy('admin_menu_id')
            ->orderBy('admin_sub_menu_id')
            ->orderBy('parent_id')
            ->orderBy('id')
            ->get();

        return $this->successResponse(
            __('messages.permissions_fetched'),
            $permissions
        );
    }

    public function parentPermissions()
    {
        if (!PermissionHelper::checkPermission(Constants::MANAGE_PERMISSIONS)) {
            return $this->failureResponse(
                __('messages.no_permission'),
                null,
                Constants::RESPONSE_FORBIDDEN
            );
        }

        $data = $this->permission->getParentPermissions();

        if ($data['status']) {
            return $this->successResponse(
                __('messages.permissions_fetched'),
                $data['data']
            );
        }

        return $this->failureResponse($data['message'], $data['data']);
    }
}
