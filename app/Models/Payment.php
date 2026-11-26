<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = ['portal_id', 'package_id', 'subscription_id', 'amount', 'months', 'method', 'trx_id', 'note', 'paid_at', 'recorded_by'];

    protected $casts = ['paid_at' => 'datetime', 'amount' => 'float'];

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }
}
