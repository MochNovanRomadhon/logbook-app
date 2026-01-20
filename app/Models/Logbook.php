<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Logbook extends Model
{
    /**
     * Kolom yang boleh diisi secara massal (Mass Assignment).
     * Pastikan semua kolom tabel logbooks ada di sini.
     */
    protected $fillable = [
        'user_id',
        'date',
        'is_submitted', // Kolom baru untuk status finalisasi
    ];

    /**
     * Konversi tipe data otomatis.
     */
    protected $casts = [
        'date' => 'date',           // Agar otomatis jadi objek Date Carbon
        'is_submitted' => 'boolean', // Agar 0/1 di DB terbaca sebagai false/true
    ];

    // --- RELATIONSHIPS ---

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Relasi ke tabel logbook_items (anak)
    // Ini wajib ada agar Repeater di Filament berfungsi
    public function items(): HasMany
    {
        return $this->hasMany(LogbookItem::class);
    }
}