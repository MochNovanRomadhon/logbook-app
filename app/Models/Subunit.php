<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subunit extends Model
{
    protected $fillable = ['unit_id', 'name', 'is_active'];

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }
}