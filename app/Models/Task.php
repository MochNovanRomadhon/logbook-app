<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Task extends Model
{
    protected $fillable = [
        'user_id',
        'assigned_by',
        'title',
        'description',
        'notes',
        'status',
        'urgency',
        'deadline',
        'completed_at',
    ];

    protected $casts = [
        'deadline' => 'date',           // Agar otomatis jadi object Carbon
        'completed_at' => 'datetime',
        'urgency' => 'integer',
    ];

    // Pemilik Task (User Pegawai)
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Pemberi Task (Atasan/Pengawas) - Optional
    public function assigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}