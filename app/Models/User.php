<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasUuids;

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
}
