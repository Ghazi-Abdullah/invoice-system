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
                $data['message'],
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
                $data['message'],
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
                $data['message'],
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

        // Get permission first
        $permission = AdminPermission::find($id);

        if (!$permission) {
            return $this->failureResponse(
                __('messages.permission_not_found'),
                null
            );
        }

        $data = $this->permission->update($request, $permission);

        if ($data['status']) {
            return $this->successResponse(
                $data['message'],
                $data['data']
            );
        }

        return $this->failureResponse($data['message'], $data['data']);
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

        // Get permission first
        $permission = AdminPermission::find($id);

        if (!$permission) {
            return $this->failureResponse(
                __('messages.permission_not_found'),
                null
            );
        }

        $data = $this->permission->destroy($permission);

        if ($data['status']) {
            return $this->successResponse(
                $data['message'],
                $data['data']
            );
        }

        return $this->failureResponse($data['message'], $data['data']);
    }

    public function getAllPermissions()
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
        if (!PermissionHelper::checkPermission(Constants::MANAGE_PERMISSIONS)) {
            return $this->failureResponse(
                __('messages.no_permission'),
                null,
                Constants::RESPONSE_FORBIDDEN
            );
        }

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
                $data['message'],
                $data['data']
            );
        }

        return $this->failureResponse($data['message'], $data['data']);
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
                $data['message'],
                $data['data']
            );
        }

        return $this->failureResponse($data['message'], $data['data']);
    }
}
