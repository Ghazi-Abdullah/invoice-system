<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupportTicketReply extends Model
{
    use HasFactory;

    // app/Models/SupportTicketReply.php — أضف is_internal للـ fillable و casts
    protected $fillable = ['ticket_id', 'user_id', 'message', 'is_admin_reply', 'is_internal'];

    protected $casts = [
        'is_admin_reply' => 'boolean',
        'is_internal'    => 'boolean',
        'created_at'     => 'datetime',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(SupportTicket::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
