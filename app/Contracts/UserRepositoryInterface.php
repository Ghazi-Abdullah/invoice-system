<?php
// app/Contracts/UserRepositoryInterface.php
namespace App\Contracts;

interface UserRepositoryInterface extends RepositoryInterface
{
    public function register(array $data);
    public function login(array $credentials);
    public function logout();
    public function assignRole(int $userId, string $role);
    public function hasPermission(int $userId, string $permission);
}
