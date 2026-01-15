<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Unit extends Model
{
    protected $fillable = ['directorate_id', 'name', 'is_active'];

    public function directorate(): BelongsTo
    {
        return $this->belongsTo(Directorate::class);
    }

    public function subunits(): HasMany
    {
        return $this->hasMany(Subunit::class);
    }
}