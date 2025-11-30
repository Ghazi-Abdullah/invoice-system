<?php
// app/Repositories/UserRepository.php
namespace App\Repositories;

use App\Contracts\UserRepositoryInterface;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class UserRepository extends BaseRepository implements UserRepositoryInterface
{
    public function __construct(User $model)
    {
        parent::__construct($model);
    }

    public function register(array $data)
    {
        $user = $this->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'phone' => $data['phone'] ?? null,
        ]);

        $user->assignRole($data['role'] ?? 'user');

        return $user;
    }

    public function login(array $credentials)
    {
        if (!Auth::attempt($credentials)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $user = $this->findBy('email', $credentials['email']);
        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token
        ];
    }

    public function logout()
    {
        Auth::user()->currentAccessToken()->delete();
        return true;
    }

    public function assignRole(int $userId, string $role)
    {
        $user = $this->find($userId);
        $user->assignRole($role);
        return $user;
    }

    public function hasPermission(int $userId, string $permission)
    {
        $user = $this->find($userId);
        return $user->hasPermissionTo($permission);
    }
}
