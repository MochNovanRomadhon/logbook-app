<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Directorate extends Model
{
    protected $fillable = ['name', 'is_active'];

    public function units(): HasMany
    {
        return $this->hasMany(Unit::class);
    }
    
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    protected static function booted()
    {
        static::updated(function ($directorate) {
            if ($directorate->wasChanged('is_active') && !$directorate->is_active) {
                // Bulk deactivation
                $directorate->units()->update(['is_active' => false]);
                Subunit::whereIn('unit_id', $directorate->units()->pluck('id'))->update(['is_active' => false]);
                User::where('directorate_id', $directorate->id)->update(['is_active' => false]);
            }
        });
    }
}