<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SessionMeta extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'session_id',
        'key',
        'value',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class);
    }
}
