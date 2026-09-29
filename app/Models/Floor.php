<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Floor extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'property_id', 'name', 'name_en', 'floor_number', 'description', 'is_active', 'created_by',
    ];

    protected $casts = [
        'is_active'    => 'boolean',
        'floor_number' => 'integer',
    ];

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function units()
    {
        return $this->hasMany(Unit::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
