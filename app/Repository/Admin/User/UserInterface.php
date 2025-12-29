<?php

namespace App\Repository\Admin\User;

interface UserInterface
{
    public function index($request);
    public function show($id);
    public function store($request);
    public function update($request, $user);
    public function destroy($user);
    public function updateProfile($request);
    public function changePassword($request);
    public function updateStatus($user, $status);
    public function getStaffUsers();
    public function getClientUsers();
}
