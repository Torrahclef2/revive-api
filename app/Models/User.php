<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasUuids, HasApiTokens;

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
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        // Authentication
        'email',
        'password',
        'username',

        // Profile
        'display_name',
        'avatar_url',
        'headline',

        // Spiritual info
        'denomination',
        'gender',
        'level',

        // Location
        'location_city',
        'location_country',

        // Engagement
        'xp_points',
        'streak_count',
        'last_active_date',

        // Status
        'is_active',
        'email_verified_at',
        'remember_token',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'xp_points' => 'integer',
            'streak_count' => 'integer',
            'last_active_date' => 'date',
        ];
    }

    /**
     * Get circles initiated by this user.
     */
    public function circlesRequested()
    {
        return $this->hasMany(Circle::class, 'requester_id');
    }

    /**
     * Get circles received by this user.
     */
    public function circlesReceived()
    {
        return $this->hasMany(Circle::class, 'receiver_id');
    }

    /**
     * Get all active circles for this user (both requested and received).
     */
    public function activeCircles()
    {
        return Circle::where(function ($query) {
            $query->where('requester_id', $this->id)
                  ->orWhere('receiver_id', $this->id);
        })->where('status', 'accepted');
    }

    /**
     * Get all pending circles for this user (both requested and received).
     */
    public function pendingCircles()
    {
        return Circle::where(function ($query) {
            $query->where('requester_id', $this->id)
                  ->orWhere('receiver_id', $this->id);
        })->where('status', 'pending');
    }

    /**
     * Get groups created by this user.
     */
    public function groupsCreated()
    {
        return $this->hasMany(Group::class, 'creator_id');
    }

    /**
     * Get groups this user is a member of.
     */
    public function groupMemberships()
    {
        return $this->hasMany(GroupMember::class);
    }

    /**
     * Get groups this user is an admin of.
     */
    public function groupsAdmined()
    {
        return $this->hasManyThrough(
            Group::class,
            GroupMember::class,
            'user_id',
            'id',
            'id',
            'group_id'
        )->where('group_members.role', 'admin');
    }

    /**
     * Get posts created by this user in groups.
     */
    public function groupPosts()
    {
        return $this->hasMany(GroupPost::class, 'author_id');
    }

    /**
     * Get reactions by this user.
     */
    public function groupPostReactions()
    {
        return $this->hasMany(GroupPostReaction::class);
    }

    /**
     * Get prayer sessions hosted by this user.
     */
    public function hostedPrayerSessions()
    {
        return $this->hasMany(PrayerSession::class, 'host_id');
    }

    /**
     * Get prayer session memberships for this user.
     */
    public function prayerSessionMemberships()
    {
        return $this->hasMany(SessionMember::class);
    }

    /**
     * Get prayer sessions this user is admitted to.
     */
    public function prayerSessions()
    {
        return $this->hasManyThrough(
            PrayerSession::class,
            SessionMember::class,
            'user_id',
            'id',
            'id',
            'session_id'
        )->where('session_members.status', 'admitted');
    }

    /**
     * Get live prayer sessions this user is in.
     */
    public function livePrayerSessions()
    {
        return $this->prayerSessions()->where('status', 'live');
    }

    /**
     * Get session thread posts by this user.
     */
    public function sessionThreadPosts()
    {
        return $this->hasMany(SessionThreadPost::class, 'author_id');
    }

    /**
     * Get streak logs for this user.
     */
    public function streakLogs()
    {
        return $this->hasMany(StreakLog::class);
    }

    /**
     * Get circle suggestions made by this user.
     */
    public function circleSuggestionsMadeByMe()
    {
        return $this->hasMany(CircleSuggestion::class, 'from_user_id');
    }

    /**
     * Get circle suggestions received by this user.
     */
    public function circleSuggestionsReceivedByMe()
    {
        return $this->hasMany(CircleSuggestion::class, 'to_user_id');
    }

    /**
     * Get session reports filed by this user.
     */
    public function reportsIFiled()
    {
        return $this->hasMany(SessionReport::class, 'reporter_id');
    }

    /**
     * Get session reports filed about this user.
     */
    public function reportsAboutMe()
    {
        return $this->hasMany(SessionReport::class, 'reported_user_id');
    }

    /**
     * Get strikes against this user.
     */
    public function strikes()
    {
        return $this->hasMany(UserStrike::class);
    }

    /**
     * Get strikes filed by this user (as moderator).
     */
    public function strikesFiled()
    {
        return $this->hasMany(UserStrike::class, 'reported_by');
    }

    /**
     * Get all circles this user is connected to (both sent and received accepted connections).
     */
    public function circles()
    {
        return $this->activeCircles();
    }

    /**
     * Check if this user is in a circle connection with another user.
     */
    public function isInCircleWith(User $user): bool
    {
        return Circle::where(function ($query) use ($user) {
            $query->where('requester_id', $this->id)
                  ->where('receiver_id', $user->id);
        })->orWhere(function ($query) use ($user) {
            $query->where('requester_id', $user->id)
                  ->where('receiver_id', $this->id);
        })->where('status', 'accepted')
        ->exists();
    }

    /**
     * Get the XP threshold for the next user level.
     * 
     * XP Thresholds:
     * - seeker: 0
     * - rising_disciple: 500
     * - follower: 1500
     * - faithful: 3000
     * - leader: 6000
     */
    public function getXpForNextLevel(): int
    {
        $thresholds = [
            'seeker' => 0,
            'rising_disciple' => 500,
            'follower' => 1500,
            'faithful' => 3000,
            'leader' => 6000,
        ];

        $currentLevel = $this->level ?? 'seeker';
        $levels = array_keys($thresholds);
        $currentIndex = array_search($currentLevel, $levels);

        if ($currentIndex === false || $currentIndex === count($levels) - 1) {
            return $thresholds['leader']; // Max level
        }

        $nextLevel = $levels[$currentIndex + 1];
        return $thresholds[$nextLevel];
    }

    /**
     * Scope: Get active users only.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
