<?php

namespace App\Filament\Resources;

use BezhanSalleh\FilamentShield\Resources\RoleResource as ShieldRoleResource;
use Illuminate\Support\Facades\Auth; // <--- Jangan lupa import ini

class RoleResource extends ShieldRoleResource
{
    // 1. Masukkan ke grup yang sama dengan User
    protected static ?string $navigationGroup = 'Manajemen Pengguna';

    public static function getNavigationLabel(): string
    {
        return 'Hak Akses';
    }
    
    public static function getNavigationSort(): ?int
    {
        return 2; 
    }

    // 4. PERBAIKAN DI SINI
    public static function shouldRegisterNavigation(): bool
    {
        // Hanya tampilkan jika user yang login adalah 'super_admin'
        return Auth::user()?->hasRole('super_admin') ?? false;
    }

    // 5. Override fungsi grup agar tidak kembali ke default
    public static function getNavigationGroup(): ?string
    {
        return 'Manajemen Pengguna';
    }

    public static function table(\Filament\Tables\Table $table): \Filament\Tables\Table
    {
        return parent::table($table);
    }
}