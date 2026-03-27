<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SessionParticipant extends Model
{
    // No standard created_at / updated_at — uses joined_at / left_at instead
    public $timestamps = false;

    protected $fillable = [
        'session_id',
        'user_id',
        'alias',
        'role',
        'joined_at',
        'left_at',
        'is_muted',
        'is_removed',
        'muted_at',
        'removed_at',
    ];

    protected $casts = [
        'joined_at'  => 'datetime',
        'left_at'    => 'datetime',
        'muted_at'   => 'datetime',
        'removed_at' => 'datetime',
        'is_muted'   => 'boolean',
        'is_removed' => 'boolean',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
