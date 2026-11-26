<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PortalActivity extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'portal_activity_logs';

    protected $fillable = [
        'portal_id', 'event', 'method', 'path', 'route_name', 'status', 'ip',
        'user_agent', 'duration_ms', 'db_queries', 'db_writes',
    ];

    protected $casts = ['created_at' => 'datetime'];

    public function portal(): BelongsTo
    {
        return $this->belongsTo(Portal::class);
    }
}
