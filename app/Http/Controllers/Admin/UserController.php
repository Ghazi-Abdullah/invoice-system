<?php

namespace App\Http\Controllers\Admin;

use App\Traits\ResponseTrait;
use App\Http\Controllers\Controller;
use App\Constants\Constants;
use App\Helpers\PermissionHelper;
use App\Repository\Admin\User\UserInterface;
use App\Http\Requests\Admin\User\StoreUserRequest;
use App\Http\Requests\Admin\User\UpdateUserRequest;
use App\Http\Requests\Admin\User\UpdateProfileRequest;
use App\Http\Requests\Admin\User\ChangePasswordRequest;
use App\Http\Requests\Admin\AdminGroup\StoreAdminGroupRequest;
use App\Http\Requests\Admin\AdminGroup\UpdateAdminGroupRequest;
use App\Models\AdminGroup;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    use ResponseTrait;

    public $user;

    public function __construct(UserInterface $user)
    {
        $this->user = $user;
    }

    public function index(Request $request)
    {
        if (!PermissionHelper::checkPermission(Constants::VIEW_USERS)) {
            return $this->failureResponse(
                __('messages.no_permission'),
                null,
                Constants::RESPONSE_FORBIDDEN
            );
        }

        $data = $this->user->index($request);

        if ($data['status']) {
            return $this->successResponse(
                __('messages.users_fetched'),
                $data['data']
            );
        }

        return $this->failureResponse($data['message'], $data['data']);
    }

    public function show($id)
    {
        if (!PermissionHelper::checkPermission(Constants::VIEW_USERS)) {
            return $this->failureResponse(
                __('messages.no_permission'),
                null,
                Constants::RESPONSE_FORBIDDEN
            );
        }

        $data = $this->user->show($id);

        if ($data['status']) {
            return $this->successResponse(
                __('messages.user_fetched'),
                $data['data']
            );
        }

        return $this->failureResponse($data['message'], $data['data']);
    }

    public function store(StoreUserRequest $request)
    {
        if (!PermissionHelper::checkPermission(Constants::CREATE_USER)) {
            return $this->failureResponse(
                __('messages.no_permission'),
                null,
                Constants::RESPONSE_FORBIDDEN
            );
        }

        // التحقق من البريد الإلكتروني المكرر بشكل صريح
        $existingUser = User::where('email', $request->email)->first();
        if ($existingUser) {
            return $this->failureResponse(
                __('messages.email_already_registered'),
                null,
                Constants::RESPONSE_VALIDATION_ERROR
            );
        }

        $data = $this->user->store($request);

        if ($data['status']) {
            return $this->successResponse(
                __('messages.user_created'),
                $data['data'],
                Constants::RESPONSE_CREATED
            );
        }

        return $this->failureResponse($data['message'], $data['data']);
    }

    public function update(UpdateUserRequest $request, $id)
    {
        if (!PermissionHelper::checkPermission(Constants::EDIT_USER)) {
            return $this->failureResponse(
                __('messages.no_permission'),
                null,
                Constants::RESPONSE_FORBIDDEN
            );
        }

        $data = $this->user->show($id);

        if (!$data['status']) {
            return $this->failureResponse($data['message'], $data['data']);
        }

        $updateData = $this->user->update($request, $data['data']);

        if ($updateData['status']) {
            return $this->successResponse(
                __('messages.user_updated'),
                $updateData['data']
            );
        }

        return $this->failureResponse($updateData['message'], $updateData['data']);
    }

    public function destroy($id)
    {
        if (!PermissionHelper::checkPermission(Constants::DELETE_USER)) {
            return $this->failureResponse(
                __('messages.no_permission'),
                null,
                Constants::RESPONSE_FORBIDDEN
            );
        }

        $data = $this->user->show($id);

        if (!$data['status']) {
            return $this->failureResponse($data['message'], $data['data']);
        }

        $deleteData = $this->user->destroy($data['data']);

        if ($deleteData['status']) {
            return $this->successResponse(
                __('messages.user_deleted'),
                $deleteData['data']
            );
        }

        return $this->failureResponse($deleteData['message'], $deleteData['data']);
    }

    public function updateStatus(Request $request, $id)
    {
        if (!PermissionHelper::checkPermission(Constants::EDIT_USER)) {
            return $this->failureResponse(
                __('messages.no_permission'),
                null,
                Constants::RESPONSE_FORBIDDEN
            );
        }

        $request->validate([
            'is_active' => 'required|boolean'
        ]);

        $data = $this->user->show($id);

        if (!$data['status']) {
            return $this->failureResponse($data['message'], $data['data']);
        }

        $statusData = $this->user->updateStatus($data['data'], $request->is_active);

        if ($statusData['status']) {
            return $this->successResponse(
                __('messages.user_status_updated'),
                $statusData['data']
            );
        }

        return $this->failureResponse($statusData['message'], $statusData['data']);
    }

    public function profile()
    {
        $user = auth()->user();

        return $this->successResponse(
            __('messages.profile_fetched'),
            $user->load('adminGroup')
        );
    }

    public function updateProfile(UpdateProfileRequest $request)
    {
        $data = $this->user->updateProfile($request);

        if ($data['status']) {
            return $this->successResponse(
                __('messages.profile_updated'),
                $data['data']
            );
        }

        return $this->failureResponse($data['message'], $data['data']);
    }

    public function changePassword(ChangePasswordRequest $request)
    {
        $data = $this->user->changePassword($request);

        if ($data['status']) {
            return $this->successResponse(
                __('messages.password_changed'),
                $data['data']
            );
        }

        return $this->failureResponse($data['message'], $data['data']);
    }

    public function staff(Request $request)
    {
        if (!PermissionHelper::checkPermission(Constants::VIEW_USERS)) {
            return $this->failureResponse(
                __('messages.no_permission'),
                null,
                Constants::RESPONSE_FORBIDDEN
            );
        }

        $data = $this->user->getStaffUsers();

        if ($data['status']) {
            return $this->successResponse(
                __('messages.staff_users_fetched'),
                $data['data']
            );
        }

        return $this->failureResponse($data['message'], $data['data']);
    }

    public function clients(Request $request)
    {
        if (!PermissionHelper::checkPermission(Constants::VIEW_CLIENTS)) {
            return $this->failureResponse(
                __('messages.no_permission'),
                null,
                Constants::RESPONSE_FORBIDDEN
            );
        }

        $data = $this->user->getClientUsers();

        if ($data['status']) {
            return $this->successResponse(
                __('messages.client_users_fetched'),
                $data['data']
            );
        }

        return $this->failureResponse($data['message'], $data['data']);
    }

    // Admin Groups Methods
    public function adminGroups(Request $request)
    {
        if (!PermissionHelper::checkPermission(Constants::MANAGE_USER_GROUPS)) {
            return $this->failureResponse(
                __('messages.no_permission'),
                null,
                Constants::RESPONSE_FORBIDDEN
            );
        }

        $query = AdminGroup::withCount('users');

        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        $groups = $query->orderBy('id')->get();

        return $this->successResponse(
            __('messages.admin_groups_fetched'),
            $groups
        );
    }

    public function showAdminGroup($id)
    {
        if (!PermissionHelper::checkPermission(Constants::MANAGE_USER_GROUPS)) {
            return $this->failureResponse(
                __('messages.no_permission'),
                null,
                Constants::RESPONSE_FORBIDDEN
            );
        }

        $group = AdminGroup::with(['permissions', 'users'])->find($id);

        if (!$group) {
            return $this->failureResponse(
                __('messages.not_found'),
                null,
                Constants::RESPONSE_NOT_FOUND
            );
        }

        return $this->successResponse(
            __('messages.admin_group_fetched'),
            $group
        );
    }

    public function storeAdminGroup(StoreAdminGroupRequest $request)
    {
        if (!PermissionHelper::checkPermission(Constants::MANAGE_USER_GROUPS)) {
            return $this->failureResponse(
                __('messages.no_permission'),
                null,
                Constants::RESPONSE_FORBIDDEN
            );
        }

        $group = AdminGroup::create([
            'title_en' => $request->title_en,
            'title_ar' => $request->title_ar,
            'description' => $request->description,
            'is_active' => $request->is_active ?? true,
            'is_system' => false
        ]);

        return $this->successResponse(
            __('messages.admin_group_created'),
            $group,
            Constants::RESPONSE_CREATED
        );
    }

    public function updateAdminGroup(UpdateAdminGroupRequest $request, $id)
    {
        if (!PermissionHelper::checkPermission(Constants::MANAGE_USER_GROUPS)) {
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

        // Cannot update system groups
        if ($group->is_system && $group->id != Constants::SUPER_ADMIN_GROUP_ID) {
            return $this->failureResponse(
                __('messages.cannot_update_system_group'),
                null,
                Constants::RESPONSE_FORBIDDEN
            );
        }

        $group->update([
            'title_en' => $request->title_en,
            'title_ar' => $request->title_ar,
            'description' => $request->description,
            'is_active' => $request->is_active
        ]);

        return $this->successResponse(
            __('messages.admin_group_updated'),
            $group
        );
    }

    public function destroyAdminGroup($id)
    {
        if (!PermissionHelper::checkPermission(Constants::MANAGE_USER_GROUPS)) {
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

        // Cannot delete system groups
        if ($group->is_system) {
            return $this->failureResponse(
                __('messages.cannot_delete_system_group'),
                null,
                Constants::RESPONSE_FORBIDDEN
            );
        }

        // Check if group has users
        if ($group->users()->count() > 0) {
            return $this->failureResponse(
                __('messages.cannot_delete_group_with_users'),
                null,
                Constants::RESPONSE_BAD_REQUEST
            );
        }

        $group->delete();

        return $this->successResponse(
            __('messages.admin_group_deleted'),
            null
        );
    }
}
