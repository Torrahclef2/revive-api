<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SessionMember extends Model
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
    protected $table = 'session_members';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'session_id',
        'user_id',
        'status',
        'joined_at',
        'left_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'joined_at' => 'datetime',
            'left_at' => 'datetime',
        ];
    }

    /**
     * Get the prayer session this member belongs to.
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(PrayerSession::class, 'session_id');
    }

    /**
     * Get the user who is a member.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if member is admitted.
     */
    public function isAdmitted(): bool
    {
        return $this->status === 'admitted';
    }

    /**
     * Check if member has pending request.
     */
    public function isPending(): bool
    {
        return $this->status === 'requested';
    }

    /**
     * Check if member was rejected.
     */
    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    /**
     * Check if member was kicked.
     */
    public function isKicked(): bool
    {
        return $this->status === 'kicked';
    }

    /**
     * Admit the member to the session.
     */
    public function admit()
    {
        $this->update([
            'status' => 'admitted',
            'joined_at' => now(),
        ]);
    }

    /**
     * Reject the member's request.
     */
    public function reject()
    {
        $this->update(['status' => 'rejected']);
    }

    /**
     * Kick the member from the session.
     */
    public function kick()
    {
        $this->update([
            'status' => 'kicked',
            'left_at' => now(),
        ]);
    }

    /**
     * Mark member as left.
     */
    public function leave()
    {
        $this->update(['left_at' => now()]);
    }

    /**
     * Get session duration the member attended (if left_at is set).
     */
    public function getAttendanceDurationMinutes(): ?int
    {
        if (!$this->joined_at || !$this->left_at) {
            return null;
        }

        return $this->joined_at->diffInMinutes($this->left_at);
    }

    /**
     * Scope to get admitted members.
     */
    public function scopeAdmitted($query)
    {
        return $query->where('status', 'admitted');
    }

    /**
     * Scope to get pending requests.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'requested');
    }

    /**
     * Scope to get rejected members.
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    /**
     * Scope to get kicked members.
     */
    public function scopeKicked($query)
    {
        return $query->where('status', 'kicked');
    }
}
