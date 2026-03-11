<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Task extends Model
{
    protected $fillable = [
        'user_id',
        'assigned_by',
        'task_group_id',
        'title',
        'description',
        'notes',
        'status',
        'urgency',
        'deadline',
        'completed_at',
        'processed_at',
        'cancelled_at',
        'accepted_at',
    ];

    protected $casts = [
        'deadline' => 'date',
        'completed_at' => 'datetime',
        'processed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'accepted_at' => 'datetime',
        'urgency' => 'integer',
    ];

    // Pemilik Task (User Pegawai)
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    // Semua task dalam grup yang sama (ditugaskan bersamaan)
    public function groupTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'task_group_id', 'task_group_id');
    }

    // Logbook Items terkait tugas ini
    public function logbookItems(): HasMany
    {
        return $this->hasMany(LogbookItem::class)->orderByDesc('created_at');
    }

    protected static function booted()
    {
        static::updating(function ($task) {
            if ($task->isDirty('status')) {
                $now = now();
                $newStatus = $task->status;

                if ($newStatus === 'in_progress') {
                    $task->processed_at = $now;
                    $task->cancelled_at = null; // tanggal dibatalkan jadi hilang
                } elseif ($newStatus === 'completed') {
                    $task->completed_at = $now;
                    if (!$task->processed_at) {
                        $task->processed_at = $now; // misal dari menunggu langsung selesai
                    }
                } elseif ($newStatus === 'cancelled') {
                    $task->cancelled_at = $now;
                }
            }
        });
    }
}