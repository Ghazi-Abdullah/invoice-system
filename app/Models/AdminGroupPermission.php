<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminGroupPermission extends Model
{
    use HasFactory;

    protected $table = 'admin_group_permissions';

    protected $fillable = ['admin_group_id', 'permission_id'];
}
