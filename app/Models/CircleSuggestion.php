<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CircleSuggestion extends Model
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
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'circle_suggestions';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'session_id',
        'from_user_id',
        'to_user_id',
        'status',
    ];

    /**
     * Get the prayer session this suggestion came from.
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(PrayerSession::class, 'session_id');
    }

    /**
     * Get the user who suggested the connection.
     */
    public function fromUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    /**
     * Get the user being suggested for connection.
     */
    public function toUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }

    /**
     * Check if suggestion is pending.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if suggestion was accepted.
     */
    public function isAccepted(): bool
    {
        return $this->status === 'accepted';
    }

    /**
     * Check if suggestion was dismissed.
     */
    public function isDismissed(): bool
    {
        return $this->status === 'dismissed';
    }

    /**
     * Accept the suggestion.
     */
    public function accept()
    {
        $this->update(['status' => 'accepted']);
    }

    /**
     * Dismiss the suggestion.
     */
    public function dismiss()
    {
        $this->update(['status' => 'dismissed']);
    }

    /**
     * Scope to get pending suggestions.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope to get accepted suggestions.
     */
    public function scopeAccepted($query)
    {
        return $query->where('status', 'accepted');
    }

    /**
     * Scope to get dismissed suggestions.
     */
    public function scopeDismissed($query)
    {
        return $query->where('status', 'dismissed');
    }

    /**
     * Scope to get suggestions for a specific session.
     */
    public function scopeForSession($query, $sessionId)
    {
        return $query->where('session_id', $sessionId);
    }

    /**
     * Scope to get suggestions made by a user.
     */
    public function scopeFrom($query, $userId)
    {
        return $query->where('from_user_id', $userId);
    }

    /**
     * Scope to get suggestions for a user.
     */
    public function scopeTo($query, $userId)
    {
        return $query->where('to_user_id', $userId);
    }
}
