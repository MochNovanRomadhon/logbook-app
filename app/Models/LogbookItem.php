<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LogbookItem extends Model
{
    protected $guarded = [];

    // Relasi ke Logbook Induk
    public function logbook(): BelongsTo
    {
        return $this->belongsTo(Logbook::class);
    }

    // --- TAMBAHKAN INI AGAR JUDUL MUNCUL ---
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }
}