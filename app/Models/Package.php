<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Package extends Model
{
    protected $fillable = ['name', 'slug', 'price', 'duration_days', 'term_months', 'grace_days', 'grace_window_days', 'is_trial', 'active'];

    protected $casts = ['is_trial' => 'boolean', 'active' => 'boolean', 'price' => 'float'];
}
