<?php

namespace App\Repository\Admin\Dashboard;

use App\Models\User;

interface DashboardInterface
{
    public function getDashboardData(User $user): array;
}
