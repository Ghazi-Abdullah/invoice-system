<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InstallmentInterestTier extends Model
{
    use HasFactory;

    protected $fillable = [
        'number_of_installments',
        'interest_rate',
        'is_active',
    ];

    protected $casts = [
        'number_of_installments' => 'integer',
        'interest_rate'          => 'decimal:2',
        'is_active'              => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
