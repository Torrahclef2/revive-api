<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PrayerSession extends Model
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
    protected $table = 'prayer_sessions';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'host_id',
        'title',
        'description',
        'purpose',
        'template',
        'visibility',
        'status',
        'gender_preference',
        'location_city',
        'location_country',
        'max_members',
        'scheduled_at',
        'live_started_at',
        'live_ended_at',
        'duration_minutes',
        'agora_channel_name',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'live_started_at' => 'datetime',
            'live_ended_at' => 'datetime',
        ];
    }

    /**
     * Get the user who hosts this session.
     */
    public function host(): BelongsTo
    {
        return $this->belongsTo(User::class, 'host_id');
    }

    /**
     * Get all members in this session.
     */
    public function members(): HasMany
    {
        return $this->hasMany(SessionMember::class, 'session_id');
    }

    /**
     * Get all thread posts for this session.
     */
    public function threadPosts(): HasMany
    {
        return $this->hasMany(SessionThreadPost::class, 'session_id');
    }

    /**
     * Get admitted members count.
     */
    public function admittedMembersCount(): int
    {
        return $this->members()->where('status', 'admitted')->count();
    }

    /**
     * Get admitted members.
     */
    public function admittedMembers()
    {
        return $this->members()->where('status', 'admitted');
    }

    /**
     * Get pending join requests.
     */
    public function pendingRequests()
    {
        return $this->members()->where('status', 'requested');
    }

    /**
     * Check if session is full.
     */
    public function isFull(): bool
    {
        return $this->admittedMembersCount() >= $this->max_members;
    }

    /**
     * Check if user is a member (admitted).
     */
    public function hasMember($userId): bool
    {
        return $this->admittedMembers()->where('user_id', $userId)->exists();
    }

    /**
     * Check if user has a pending request.
     */
    public function hasPendingRequest($userId): bool
    {
        return $this->pendingRequests()->where('user_id', $userId)->exists();
    }

    /**
     * Get available member spots.
     */
    public function availableSpots(): int
    {
        return max(0, $this->max_members - $this->admittedMembersCount());
    }

    /**
     * Scope to get upcoming sessions.
     */
    public function scopeUpcoming($query)
    {
        return $query->where('status', 'upcoming')->orderBy('scheduled_at');
    }

    /**
     * Scope to get live sessions.
     */
    public function scopeLive($query)
    {
        return $query->where('status', 'live');
    }

    /**
     * Scope to get ended sessions.
     */
    public function scopeEnded($query)
    {
        return $query->where('status', 'ended');
    }

    /**
     * Scope to filter by purpose.
     */
    public function scopeByPurpose($query, $purpose)
    {
        return $query->where('purpose', $purpose);
    }

    /**
     * Scope to filter by template.
     */
    public function scopeByTemplate($query, $template)
    {
        return $query->where('template', $template);
    }

    /**
     * Scope to filter by visibility.
     */
    public function scopeByVisibility($query, $visibility)
    {
        return $query->where('visibility', $visibility);
    }

    /**
     * Scope to filter by location country.
     */
    public function scopeByCountry($query, $country)
    {
        return $query->where('location_country', $country);
    }

    /**
     * Scope to filter by gender preference.
     */
    public function scopeByGenderPreference($query, $gender)
    {
        return $query->where('gender_preference', ['any', $gender]);
    }
}
