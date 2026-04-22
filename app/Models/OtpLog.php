<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OtpLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'otp',
        'type',
        'status',
        'attempts',
        'ip',
        'verified_at'
    ];

    protected $casts = [
        'verified_at' => 'datetime',
    ];

    // علاقة مع المستخدم
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
