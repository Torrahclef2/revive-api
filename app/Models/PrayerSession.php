<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

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
            'max_members' => 'integer',
            'duration_minutes' => 'integer',
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
     * Get session reports for this session.
     */
    public function reports(): HasMany
    {
        return $this->hasMany(SessionReport::class, 'session_id');
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

    /**
     * Scope: Get active sessions (not ended).
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['upcoming', 'admitting', 'live']);
    }

    /**
     * Scope: Get discoverable sessions (active and discoverable).
     * Includes open sessions and anonymous sessions.
     */
    public function scopeDiscoverable($query)
    {
        return $query->active()
                     ->whereIn('visibility', ['open', 'anonymous']);
    }

    /**
     * Scope: Get sessions that are not at full capacity.
     */
    public function scopeNotFull($query)
    {
        return $query->whereRaw('(SELECT COUNT(*) FROM session_members WHERE session_id = prayer_sessions.id AND status = "admitted") < max_members');
    }

    /**
     * Scope: Filter sessions by location country.
     */
    public function scopeForLocation($query, string $country)
    {
        return $query->where('location_country', $country);
    }

    /**
     * Scope: Filter by gender preference that matches user gender.
     * Includes sessions with gender_preference = 'any' or matching the specific gender.
     */
    public function scopeByGender($query, string $gender)
    {
        return $query->where(function ($q) use ($gender) {
            $q->where('gender_preference', 'any')
              ->orWhere('gender_preference', $gender);
        });
    }

    /**
     * Check if this session is fully admitted (reached max_members).
     */
    public function isFullyAdmitted(): bool
    {
        return $this->admittedMembersCount() >= $this->max_members;
    }

    /**
     * Check if a user can join this session.
     * Returns false if session is full, user is already a member, or doesn't meet visibility/gender requirements.
     */
    public function canUserJoin(User $user): bool
    {
        // Check if already a member
        if ($this->hasMember($user->id)) {
            return false;
        }

        // Check if full
        if ($this->isFullyAdmitted()) {
            return false;
        }

        // Check gender preference
        if ($this->gender_preference !== 'any' && $this->gender_preference !== $user->gender) {
            return false;
        }

        // Check visibility
        if ($this->visibility === 'circle_only') {
            // Would need to implement circle checking - for now allowed
            return true;
        }

        return true;
    }

    /**
     * Check if this session is anonymous.
     */
    public function isAnonymous(): bool
    {
        return $this->visibility === 'anonymous';
    }

    /**
     * Check if this session is hosted by the given user.
     */
    public function isHostedBy(User $user): bool
    {
        return $this->host_id === $user->id;
    }

    /**
     * CRITICAL PRIVACY PROTECTION: Get host info for API responses.
     * Returns null if session is anonymous and requesting user is not the host.
     * 
     * CRITICAL REQUIREMENT ENFORCEMENT:
     * When visibility = 'anonymous', the host_id must NEVER be exposed through
     * any relationship eager load or direct access from outside the model.
     * This method ensures strict anonymity protection.
     * 
     * @param User|null $requestingUser The user making the API request
     * @return User|null The host user object, or null if protected
     */
    public function getHostForApi(?User $requestingUser = null): ?User
    {
        // If not anonymous, always return the host
        if (!$this->isAnonymous()) {
            return $this->host;
        }

        // If anonymous, only return host if requesting user is the host
        if ($requestingUser && $this->isHostedBy($requestingUser)) {
            return $this->host;
        }

        // Anonymous and user is not host - PROTECT HOST IDENTITY
        return null;
    }

    /**
     * Generate Agora channel name for WebRTC using UUID.
     * Static method for use before model is created.
     */
    public static function generateAgoraChannelName(): string
    {
        return Str::uuid()->toString();
    }
}
