<?php
// app/Repository/User/User/UserInterface.php
namespace App\Repository\User\User;

interface UserInterface
{
    public function index();
    public function show($user);
    public function store($request);
    public function update($request, $user);
    public function destroy($user);
}
