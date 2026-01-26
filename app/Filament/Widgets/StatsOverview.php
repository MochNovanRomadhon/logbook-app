<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;
use App\Models\User;

class StatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;
    protected static ?string $pollingInterval = '30s';
    protected static bool $isLazy = true;

    protected function getColumns(): int
    {
        return 2;
    }

    public static function canView(): bool
    {
        return Auth::user()->hasRole('super_admin');
    }

    protected function getStats(): array
    {
        return [
            Stat::make('Total Role', Role::count())
                ->description('Role & Hak Akses Tersedia')
                ->descriptionIcon('heroicon-m-shield-check')
                // Pola: Stabil lalu naik sedikit (seperti tangga)
                ->chart([1, 2, 2, 3, 3, 4, 4, 6, 6, 8]) 
                ->color('primary'),

            Stat::make('Pengguna Aktif', User::where('is_active', true)->count())
                ->description("Dari total akun terdaftar")
                ->descriptionIcon('heroicon-m-user-group')
                // Pola: Gelombang Aktif (Naik Turun Cepat)
                ->chart([15, 4, 10, 2, 12, 4, 12, 8, 20]) 
                ->color('success'),
        ];
    }
}