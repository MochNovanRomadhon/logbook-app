<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles; // <--- PENTING: Import ini

class User extends Authenticatable implements FilamentUser // Implement Interface ini
{
    use HasFactory, Notifiable, HasRoles; // <--- PENTING: Tambahkan HasRoles

    protected $fillable = [
        'name',
        'email',
        'password',
        'directorate_id',
        'unit_id',
        'subunit_id',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
    ];
    
    // Konfigurasi akses panel (siapa yang boleh login)
    public function canAccessPanel(Panel $panel): bool
    {
        // Hanya user aktif yang bisa login
        return $this->is_active; 
    }

    // --- RELASI ---

    public function directorate(): BelongsTo
    {
        return $this->belongsTo(Directorate::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function subunit(): BelongsTo
    {
        return $this->belongsTo(Subunit::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }
}