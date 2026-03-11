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

    protected static function booted()
    {
        static::saving(function ($item) {
            if ($item->task_id && $item->logbook_id) {
                // Determine the date of the logbook
                $logbookDate = $item->logbook->date ?? \App\Models\Logbook::find($item->logbook_id)?->date;
                
                if ($logbookDate) {
                    $previousItem = self::where('task_id', $item->task_id)
                        ->where('id', '!=', $item->id ?? 0)
                        ->whereHas('logbook', function ($query) use ($logbookDate) {
                            $query->where('date', '<', $logbookDate);
                        })
                        ->join('logbooks', 'logbooks.id', '=', 'logbook_items.logbook_id')
                        ->orderByDesc('logbooks.date')
                        ->orderByDesc('logbook_items.id')
                        ->select('logbook_items.*')
                        ->first();
                        
                    $item->previous_progress = $previousItem ? $previousItem->current_progress : 0;
                }
            }
        });

        static::saved(function ($item) {
            if ($item->task_id && $item->logbook_id) {
                $logbookDate = $item->logbook->date ?? \App\Models\Logbook::find($item->logbook_id)?->date;
                
                if ($logbookDate) {
                    $nextItem = self::where('task_id', $item->task_id)
                        ->where('id', '!=', $item->id)
                        ->whereHas('logbook', function ($query) use ($logbookDate) {
                            $query->where('date', '>', $logbookDate);
                        })
                        ->join('logbooks', 'logbooks.id', '=', 'logbook_items.logbook_id')
                        ->orderBy('logbooks.date', 'asc')
                        ->orderBy('logbook_items.id', 'asc')
                        ->select('logbook_items.*')
                        ->first();
                        
                    if ($nextItem && $nextItem->previous_progress !== $item->current_progress) {
                        self::withoutEvents(function () use ($nextItem, $item) {
                            $nextItem->update(['previous_progress' => $item->current_progress]);
                        });
                    }
                }
            }
        });

        static::deleted(function ($item) {
            if ($item->task_id && $item->logbook_id) {
                $logbookDate = $item->logbook->date ?? \App\Models\Logbook::find($item->logbook_id)?->date;
                
                if ($logbookDate) {
                    $previousItem = self::where('task_id', $item->task_id)
                        ->where('id', '!=', $item->id)
                        ->whereHas('logbook', function ($query) use ($logbookDate) {
                            $query->where('date', '<', $logbookDate);
                        })
                        ->join('logbooks', 'logbooks.id', '=', 'logbook_items.logbook_id')
                        ->orderByDesc('logbooks.date')
                        ->orderByDesc('logbook_items.id')
                        ->select('logbook_items.*')
                        ->first();

                    $nextItem = self::where('task_id', $item->task_id)
                        ->where('id', '!=', $item->id)
                        ->whereHas('logbook', function ($query) use ($logbookDate) {
                            $query->where('date', '>', $logbookDate);
                        })
                        ->join('logbooks', 'logbooks.id', '=', 'logbook_items.logbook_id')
                        ->orderBy('logbooks.date', 'asc')
                        ->orderBy('logbook_items.id', 'asc')
                        ->select('logbook_items.*')
                        ->first();

                    if ($nextItem) {
                        $newPrevProgress = $previousItem ? $previousItem->current_progress : 0;
                        self::withoutEvents(function () use ($nextItem, $newPrevProgress) {
                            $nextItem->update(['previous_progress' => $newPrevProgress]);
                        });
                    }
                }
            }
        });
    }
}