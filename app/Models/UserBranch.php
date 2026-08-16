<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class UserBranch extends Pivot
{
    protected $table = 'user_branches';

    protected $fillable = [
        'user_id',
        'branch_id',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
