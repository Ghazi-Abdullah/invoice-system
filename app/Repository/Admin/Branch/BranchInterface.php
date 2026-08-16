<?php

namespace App\Repository\Admin\Branch;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

interface BranchInterface
{
    public function getAll(array $filters = []): Collection;
    public function getActive(): Collection;
    public function findById(int $id): ?Branch;
    public function findByCode(string $code): ?Branch;
    public function create(array $data): Branch;
    public function update(Branch $branch, array $data): Branch;
    public function delete(Branch $branch): bool;
    public function getUserBranches(User $user): Collection;
    public function getUserDefaultBranch(User $user): ?Branch;
    public function syncUserBranches(User $user, array $branchIds, ?int $defaultBranchId = null): void;
    public function setUserDefaultBranch(User $user, int $branchId): void;
}