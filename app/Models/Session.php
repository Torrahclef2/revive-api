<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Session extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'description',
        'host_id',
        'max_participants',
        'duration',
        'privacy',
        'status',
        'started_at',
        'ended_at',
        'scheduled_at',
        'reminder_sent',
    ];

    protected $casts = [
        'started_at'    => 'datetime',
        'ended_at'      => 'datetime',
        'scheduled_at'  => 'datetime',
        'reminder_sent' => 'boolean',
    ];

    public function host(): BelongsTo
    {
        return $this->belongsTo(User::class, 'host_id');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(SessionParticipant::class);
    }

    public function meta(): HasMany
    {
        return $this->hasMany(SessionMeta::class);
    }

    // Only participants who have not left yet
    public function activeParticipants(): HasMany
    {
        return $this->participants()->whereNull('left_at');
    }
}
