<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContentPage extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'title',
        'introduction',
        'sections',
        'version',
        'effective_date',
        'acceptance_text',
        'is_active',
    ];

    protected $casts = [
        'sections' => 'array',
        'effective_date' => 'date',
        'is_active' => 'boolean',
    ];
}
