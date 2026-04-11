<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SessionReport extends Model
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
    protected $table = 'session_reports';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'session_id',
        'reporter_id',
        'reported_user_id',
        'reason',
        'stage',
        'reviewed',
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
            'reviewed' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    /**
     * Get the prayer session this report is about.
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(PrayerSession::class, 'session_id');
    }

    /**
     * Get the user who reported.
     */
    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    /**
     * Get the user being reported.
     */
    public function reportedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_user_id');
    }

    /**
     * Check if report was during session.
     */
    public function isDuringSession(): bool
    {
        return $this->stage === 'during';
    }

    /**
     * Check if report was after session.
     */
    public function isAfterSession(): bool
    {
        return $this->stage === 'after';
    }

    /**
     * Mark report as reviewed.
     */
    public function markReviewed()
    {
        $this->update(['reviewed' => true]);
    }

    /**
     * Scope to get unreviewed reports.
     */
    public function scopeUnreviewed($query)
    {
        return $query->where('reviewed', false);
    }

    /**
     * Scope to get reviewed reports.
     */
    public function scopeReviewed($query)
    {
        return $query->where('reviewed', true);
    }

    /**
     * Scope to get reports from during session.
     */
    public function scopeDuringSession($query)
    {
        return $query->where('stage', 'during');
    }

    /**
     * Scope to get reports from after session.
     */
    public function scopeAfterSession($query)
    {
        return $query->where('stage', 'after');
    }

    /**
     * Scope to get reports about a specific user.
     */
    public function scopeAboutUser($query, $userId)
    {
        return $query->where('reported_user_id', $userId);
    }

    /**
     * Scope to get reports for a specific session.
     */
    public function scopeForSession($query, $sessionId)
    {
        return $query->where('session_id', $sessionId);
    }
}
