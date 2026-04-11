<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Group extends Model
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
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'creator_id',
        'name',
        'purpose',
        'description',
        'avatar_url',
        'is_active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the user who created this group.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    /**
     * Get all members of this group.
     */
    public function members(): HasMany
    {
        return $this->hasMany(GroupMember::class);
    }

    /**
     * Get all posts in this group.
     */
    public function posts(): HasMany
    {
        return $this->hasMany(GroupPost::class);
    }

    /**
     * Get all active members (admin and members).
     */
    public function activeMembers()
    {
        return $this->members()->count();
    }

    /**
     * Get admin members.
     */
    public function admins()
    {
        return $this->members()->where('role', 'admin');
    }

    /**
     * Check if a user is a member of this group.
     */
    public function isMember($userId): bool
    {
        return $this->members()->where('user_id', $userId)->exists();
    }

    /**
     * Check if a user is an admin of this group.
     */
    public function isAdmin(User $user): bool
    {
        return $this->members()
            ->where('user_id', $user->id)
            ->where('role', 'admin')
            ->exists();
    }

    /**
     * Scope to get only active groups.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter by purpose.
     */
    public function scopeByPurpose($query, $purpose)
    {
        return $query->where('purpose', $purpose);
    }
}
