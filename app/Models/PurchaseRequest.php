<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseRequest extends Model
{
    protected $fillable = [
        'portal_id', 'package_id', 'months', 'amount', 'method', 'trx_id', 'note',
        'status', 'admin_note', 'payment_id', 'decided_at', 'decided_by',
    ];

    protected $casts = ['amount' => 'float', 'decided_at' => 'datetime'];

    public function portal(): BelongsTo
    {
        return $this->belongsTo(Portal::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
}
