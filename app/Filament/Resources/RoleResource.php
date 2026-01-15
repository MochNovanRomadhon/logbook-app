<?php

namespace App\Filament\Resources;

use BezhanSalleh\FilamentShield\Resources\RoleResource as ShieldRoleResource;

class RoleResource extends ShieldRoleResource
{
    // 1. Masukkan ke grup yang sama dengan User
    protected static ?string $navigationGroup = 'Manajemen User';

    public static function getNavigationLabel(): string
    {
        return 'Hak Akses';
    }
    
    public static function getNavigationSort(): ?int
    {
        return 2; // Angka 2 pasti kalahkan Angka 1 (User)
    }
    // 4. Kita paksa menu ini TAMPIL (karena yang asli tadi sudah di-false-kan)
    public static function shouldRegisterNavigation(): bool
    {
        return true; 
    }

    // 5. Override fungsi grup agar tidak kembali ke default
    public static function getNavigationGroup(): ?string
    {
        return 'Manajemen Pengguna';
    }
}