<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PortalCharge extends Model
{
    protected $fillable = ['portal_id', 'purpose', 'amount', 'type', 'paid_at'];

    protected $casts = ['paid_at' => 'datetime', 'amount' => 'float'];
}
