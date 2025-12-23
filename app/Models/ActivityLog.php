<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ActivityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'action',
        'description',
        'model_type',
        'model_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent'
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    // Relations
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForModel($query, $modelType, $modelId = null)
    {
        $query = $query->where('model_type', $modelType);

        if ($modelId) {
            $query->where('model_id', $modelId);
        }

        return $query;
    }

    // Methods
    public static function log($action, $description, $model = null, $oldValues = null, $newValues = null)
    {
        $log = new self();
        $log->user_id = auth()->id();
        $log->action = $action;
        $log->description = $description;

        if ($model) {
            $log->model_type = get_class($model);
            $log->model_id = $model->id;
        }

        $log->old_values = $oldValues;
        $log->new_values = $newValues;
        $log->ip_address = request()->ip();
        $log->user_agent = request()->userAgent();

        $log->save();

        return $log;
    }
}
