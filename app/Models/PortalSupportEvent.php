<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PortalSupportEvent extends Model
{
    public $timestamps = false;

    protected $fillable = ['support_session_id', 'action', 'path'];

    protected $casts = ['created_at' => 'datetime'];

    public function supportSession(): BelongsTo
    {
        return $this->belongsTo(PortalSupportSession::class, 'support_session_id');
    }
}
