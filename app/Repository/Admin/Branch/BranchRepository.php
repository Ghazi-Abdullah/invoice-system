<?php

namespace App\Repository\Admin\Branch;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class BranchRepository
{
    public function getAll(array $filters = []): Collection
    {
        $query = Branch::query();

        if (!empty($filters['search'])) {
            $search = substr(trim($filters['search']), 0, 100);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('name_en', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        return $query->orderBy('name')->get();
    }

    public function getActive(): Collection
    {
        return Branch::active()->orderBy('name')->get();
    }

    public function findById(int $id): ?Branch
    {
        return Branch::find($id);
    }

    public function findByCode(string $code): ?Branch
    {
        return Branch::where('code', $code)->first();
    }

    public function create(array $data): Branch
    {
        return Branch::create($data);
    }

    public function update(Branch $branch, array $data): Branch
    {
        $branch->update($data);
        return $branch->fresh();
    }

    public function delete(Branch $branch): bool
    {
        return $branch->delete();
    }

    public function getUserBranches(User $user): Collection
    {
        if ($user->isSuperAdmin()) {
            return $this->getActive();
        }

        return $user->branches()->where('branches.is_active', true)->get();
    }

    public function getUserDefaultBranch(User $user): ?Branch
    {
        if ($user->isSuperAdmin()) {
            return Branch::main()->first() ?? Branch::active()->first();
        }

        return $user->branches()
            ->where('user_branches.is_default', true)
            ->where('branches.is_active', true)
            ->first();
    }

    public function syncUserBranches(User $user, array $branchIds, ?int $defaultBranchId = null): void
    {
        $syncData = [];

        foreach ($branchIds as $branchId) {
            $syncData[$branchId] = [
                'is_default' => ($defaultBranchId && $defaultBranchId == $branchId),
            ];
        }

        $user->branches()->sync($syncData);
    }

    public function setUserDefaultBranch(User $user, int $branchId): void
    {
        // Reset all defaults
        $user->branches()->updateExistingPivot(
            $user->branches()->pluck('branches.id')->toArray(),
            ['is_default' => false]
        );

        // Set new default
        if ($user->branches()->where('branch_id', $branchId)->exists()) {
            $user->branches()->updateExistingPivot($branchId, ['is_default' => true]);
        }
    }
}
