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

    protected static function booted()
    {
        static::updated(function ($subunit) {
            if ($subunit->wasChanged('is_active') && !$subunit->is_active) {
                User::where('subunit_id', $subunit->id)->update(['is_active' => false]);
            }
        });
    }
}