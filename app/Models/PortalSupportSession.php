<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PortalSupportSession extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'admin_id', 'admin_name', 'portal_id', 'portal_name',
        'started_at', 'last_activity_at', 'ended_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'last_activity_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    public function portal(): BelongsTo
    {
        return $this->belongsTo(Portal::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(PortalSupportEvent::class, 'support_session_id');
    }
}
