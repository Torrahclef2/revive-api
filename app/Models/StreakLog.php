<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StreakLog extends Model
{
    use HasFactory, HasUuids;

    /**
     * The primary key type.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * Disable updated_at since we only need created_at.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'streak_logs';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'session_id',
        'activity_date',
        'activity_type',
        'xp_earned',
        'created_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'activity_date' => 'date',
            'created_at' => 'datetime',
        ];
    }

    /**
     * Get the user who performed the activity.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the prayer session (if applicable).
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(PrayerSession::class, 'session_id');
    }

    /**
     * Check if activity was hosting a session.
     */
    public function isHostActivity(): bool
    {
        return $this->activity_type === 'hosted_session';
    }

    /**
     * Check if activity was joining a session.
     */
    public function isJoinActivity(): bool
    {
        return $this->activity_type === 'joined_session';
    }

    /**
     * Scope to get logs for a specific date.
     */
    public function scopeForDate($query, $date)
    {
        return $query->where('activity_date', $date);
    }

    /**
     * Scope to get only hosting activities.
     */
    public function scopeHostedSessions($query)
    {
        return $query->where('activity_type', 'hosted_session');
    }

    /**
     * Scope to get only joined activities.
     */
    public function scopeJoinedSessions($query)
    {
        return $query->where('activity_type', 'joined_session');
    }

    /**
     * Scope to get logs from the last N days.
     */
    public function scopeLastDays($query, $days = 7)
    {
        return $query->where('activity_date', '>=', today()->subDays($days));
    }
}
