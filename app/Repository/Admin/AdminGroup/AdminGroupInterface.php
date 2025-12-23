<?php

namespace App\Repository\Admin\AdminGroup;

interface AdminGroupInterface
{
    public function index($request);
    public function show($id);
    public function store($request);
    public function update($request, $adminGroup);
    public function destroy($adminGroup);
    public function getPermissions($adminGroup);
    public function updatePermissions($adminGroup, $permissions);
    public function getAvailablePermissions();
    public function getGroupsWithPermissions();
    public function getSimpleList();
}
