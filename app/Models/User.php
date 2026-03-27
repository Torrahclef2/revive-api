<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'avatar',
        'headline',
        'is_verified',
        'messaging_privacy',
        'streak',
        'level',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password'    => 'hashed',
            'is_verified' => 'boolean',
        ];
    }

    // Sessions this user hosts
    public function hostedSessions(): HasMany
    {
        return $this->hasMany(Session::class, 'host_id');
    }

    // All participation records across sessions
    public function sessionParticipants(): HasMany
    {
        return $this->hasMany(SessionParticipant::class);
    }

    // Groups this user belongs to
    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class, 'group_members', 'user_id', 'group_id')
            ->withPivot('role')
            ->withTimestamps();
    }

    // Conversations this user is part of
    public function conversations(): BelongsToMany
    {
        return $this->belongsToMany(Conversation::class, 'conversation_participants')
            ->withTimestamps();
    }
}
