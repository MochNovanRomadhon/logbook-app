<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use App\Models\Directorate;
use App\Models\Unit;
use App\Models\Subunit;

class OrganizationStatsOverview extends BaseWidget
{
    protected static ?int $sort = 2;
    protected static ?string $pollingInterval = '30s';
    protected static bool $isLazy = true;

    // Pastikan ini tetap 3 agar layout terjaga
    protected int | string | array $columns = 3; 

    public static function canView(): bool
    {
        return Auth::user()->hasRole('super_admin');
    }

    protected function getStats(): array
    {
        return [
            Stat::make('Total Direktorat', Directorate::count())
                ->description('Level 1')
                ->descriptionIcon('heroicon-m-building-library')
                ->color('info')
                // Pola: Landai (Curve Halus)
                ->chart([1, 3, 5, 10, 15, 20, 25]),

            Stat::make('Total Unit', Unit::count())
                ->description('Level 2')
                ->descriptionIcon('heroicon-m-building-office')
                ->color('warning')
                // Pola: Zig-zag Tajam (Naik Turun Drastis)
                ->chart([30, 10, 40, 10, 30, 10, 50, 20]),

            Stat::make('Total Sub-unit', Subunit::count())
                ->description('Level 3')
                ->descriptionIcon('heroicon-m-building-storefront')
                ->color('danger')
                // Pola: Memuncak di akhir (Eksponensial)
                ->chart([2, 2, 3, 5, 8, 13, 21, 34]),
        ];
    }
}