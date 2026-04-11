<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GroupPost extends Model
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
    protected $table = 'group_posts';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'group_id',
        'author_id',
        'content',
    ];

    /**
     * Get the group this post belongs to.
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    /**
     * Get the author of this post.
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * Get all reactions to this post.
     */
    public function reactions(): HasMany
    {
        return $this->hasMany(GroupPostReaction::class, 'post_id');
    }

    /**
     * Get reaction counts by type.
     */
    public function getReactionCounts()
    {
        return $this->reactions()
            ->groupBy('reaction')
            ->selectRaw('reaction, count(*) as count')
            ->pluck('count', 'reaction');
    }

    /**
     * Check if user has reacted to this post.
     */
    public function hasReaction($userId, $reaction = null): bool
    {
        $query = $this->reactions()->where('user_id', $userId);

        if ($reaction) {
            $query->where('reaction', $reaction);
        }

        return $query->exists();
    }

    /**
     * Get user's reaction to this post.
     */
    public function getUserReaction($userId)
    {
        return $this->reactions()
            ->where('user_id', $userId)
            ->first();
    }

    /**
     * Scope to order by latest posts.
     */
    public function scopeLatest($query)
    {
        return $query->orderByDesc('created_at');
    }
}
