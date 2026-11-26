<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GraceRequest extends Model
{
    protected $fillable = [
        'portal_id', 'until_date', 'reason', 'status', 'admin_note',
        'approved_until', 'decided_at', 'decided_by',
    ];

    protected $casts = [
        'until_date' => 'date',
        'approved_until' => 'date',
        'decided_at' => 'datetime',
    ];

    public function portal(): BelongsTo
    {
        return $this->belongsTo(Portal::class);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
}
