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
}