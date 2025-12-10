<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PermissionController extends Controller
{
    public function getRoles()
    {
        $roles = Role::with('permissions')->get();
        return response()->json($roles);
    }

    public function getPermissions()
    {
        $permissions = Permission::all();
        return response()->json($permissions);
    }

    public function createRole(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:roles',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $role = Role::create($request->only('name', 'description'));
        return response()->json($role, 201);
    }

    public function updateRole(Request $request, $id)
    {
        $role = Role::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:roles,name,' . $id,
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $role->update($request->only('name', 'description'));
        return response()->json($role);
    }

    public function deleteRole($id)
    {
        $role = Role::findOrFail($id);
        $role->delete();

        return response()->json(['message' => 'Role deleted successfully']);
    }

    public function assignPermissionsToRole(Request $request, $roleId)
    {
        $role = Role::findOrFail($roleId);

        $validator = Validator::make($request->all(), [
            'permissions' => 'required|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $role->permissions()->sync($request->permissions);
        $role->load('permissions');

        return response()->json($role);
    }

    public function assignRolesToUser(Request $request, $userId)
    {
        $user = User::findOrFail($userId);

        $validator = Validator::make($request->all(), [
            'roles' => 'required|array',
            'roles.*' => 'exists:roles,id',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user->roles()->sync($request->roles);
        $user->load('roles');

        return response()->json($user);
    }

    public function getUserRoles($userId)
    {
        $user = User::with('roles.permissions')->findOrFail($userId);
        return response()->json($user);
    }

    public function getUsersWithRoles()
    {
        $users = User::with('roles')->get();
        return response()->json($users);
    }
}
