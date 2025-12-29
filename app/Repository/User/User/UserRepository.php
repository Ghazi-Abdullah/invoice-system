<?php

namespace App\Repository\User\User;

use App\Models\User;
use App\Models\AdminGroup;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Constants\Constants;

class UserRepository implements UserInterface
{
    public function index()
    {
        $user = Auth::user();
        if (!$user->hasPermission(Constants::VIEW_USERS)) {
            throw new \Exception(__('messages.no_permission'));
        }

        return User::with(['group', 'permissions'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function show(User $user)
    {
        $authUser = Auth::user();
        if (!$authUser->hasPermission(Constants::VIEW_USERS)) {
            throw new \Exception(__('messages.no_permission'));
        }

        return $user->load(['group', 'permissions']);
    }

    public function store($request)
    {
        $authUser = Auth::user();
        if (!$authUser->hasPermission(Constants::CREATE_USER)) {
            throw new \Exception(__('messages.no_permission'));
        }

        $data = $request->validated();
        $data['password'] = Hash::make($data['password']);

        $user = User::create($data);

        if (isset($data['admin_group_id'])) {
            $user->update(['admin_group_id' => $data['admin_group_id']]);
        }

        Log::info('User created by admin', [
            'admin_id' => $authUser->id,
            'user_id' => $user->id
        ]);

        return $user;
    }

    public function update($request, User $user)
    {
        $authUser = Auth::user();
        if (!$authUser->hasPermission(Constants::EDIT_USER)) {
            throw new \Exception(__('messages.no_permission'));
        }

        $data = $request->validated();

        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $user->update($data);

        Log::info('User updated by admin', [
            'admin_id' => $authUser->id,
            'user_id' => $user->id
        ]);

        return $user;
    }

    public function destroy(User $user)
    {
        $authUser = Auth::user();
        if (!$authUser->hasPermission(Constants::DELETE_USER)) {
            throw new \Exception(__('messages.no_permission'));
        }

        // منع حذف المستخدم الحالي
        if ($user->id === $authUser->id) {
            throw new \Exception(__('messages.cannot_delete_own_account'));
        }

        // منع حذف مستخدمين لديهم فواتير أو عملاء
        if ($user->invoices()->count() > 0 || $user->clients()->count() > 0) {
            throw new \Exception(__('messages.user_has_invoices_or_clients'));
        }

        $user->delete();

        Log::info('User deleted by admin', [
            'admin_id' => $authUser->id,
            'user_id' => $user->id
        ]);

        return true;
    }

    public function updateStatus(User $user, $status)
    {
        $authUser = Auth::user();
        if (!$authUser->hasPermission(Constants::EDIT_USER)) {
            throw new \Exception(__('messages.no_permission'));
        }

        $user->update(['is_active' => $status]);

        return $user;
    }

    public function assignGroup(User $user, $groupId)
    {
        $authUser = Auth::user();
        if (!$authUser->hasPermission(Constants::EDIT_USER)) {
            throw new \Exception(__('messages.no_permission'));
        }

        $group = AdminGroup::find($groupId);
        if (!$group) {
            throw new \Exception(__('messages.group_not_found'));
        }

        $user->update(['admin_group_id' => $groupId]);

        return $user;
    }
}
