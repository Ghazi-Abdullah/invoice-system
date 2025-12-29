<?php
// app/Models/AdminGroupPermission.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminGroupPermission extends Model
{
    use HasFactory;

    protected $table = 'admin_group_permissions';

    protected $fillable = [
        'admin_group_id',
        'admin_permission_id'
    ];

    public function group()
    {
        return $this->belongsTo(AdminGroup::class, 'admin_group_id');
    }

    public function permission()
    {
        return $this->belongsTo(AdminPermission::class, 'admin_permission_id');
    }
}
