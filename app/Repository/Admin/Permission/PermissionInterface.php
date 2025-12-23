<?php

namespace App\Repository\Admin\Permission;

interface PermissionInterface
{
    public function index($request);
    public function show($id);
    public function store($request);
    public function update($request, $permission);
    public function destroy($permission);
    public function getAllPermissions();
}
