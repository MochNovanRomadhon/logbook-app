<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use App\Models\Task;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class TaskUrgencyChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?string $heading = 'Tingkat Urgensi';
    protected static bool $isLazy = true;
    protected static ?int $sort = 3;
    protected static ?string $maxHeight = '300px';
    protected int | string | array $columnSpan = 1;

    // 1. FITUR KHUSUS PEGAWAI
    public static function canView(): bool
    {
        return Auth::user() && Auth::user()->hasRole('pegawai');
    }

    protected function getData(): array
    {
        $user = Auth::user();
        $filters = $this->filters;

        $startDate = Carbon::parse($filters['startDate'] ?? now()->startOfWeek())->startOfDay();
        $endDate = Carbon::parse($filters['endDate'] ?? now()->endOfWeek())->endOfDay();
        
        $cacheKey = "task_urgency_{$user->id}_{$startDate->format('Y-m-d')}_{$endDate->format('Y-m-d')}";

        $data = Cache::remember($cacheKey, now()->addMinutes(15), function () use ($user, $startDate, $endDate) {
            return Task::query()
                ->where('user_id', $user->id)
                ->whereBetween('deadline', [$startDate, $endDate])
                ->selectRaw('urgency, count(*) as count')
                ->groupBy('urgency')
                ->orderBy('urgency')
                ->pluck('count', 'urgency')
                ->toArray();
        });

        return [
            'datasets' => [
                [
                    'label' => 'Jumlah Tugas',
                    'data' => [
                        $data[1] ?? 0,
                        $data[2] ?? 0,
                        $data[3] ?? 0,
                        $data[4] ?? 0,
                        $data[5] ?? 0,
                    ],
                    // 2. WARNA LEBIH INTERAKTIF:
                    // Urgensi 1 (Hijau/Santai) -> Urgensi 5 (Merah/Bahaya)
                    'backgroundColor' => [
                        '#10b981', // 1: Emerald/Hijau
                        '#3b82f6', // 2: Blue
                        '#f59e0b', // 3: Amber/Kuning
                        '#f97316', // 4: Orange
                        '#ef4444', // 5: Red
                    ],
                    'borderRadius' => 5,
                    'barThickness' => 25,
                ],
            ],
            'labels' => ['1', '2', '3', '4', '5'],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'indexAxis' => 'y', 
            'plugins' => [
                'legend' => ['display' => false],
            ],
            'scales' => [
                'x' => [
                    'beginAtZero' => true,
                    'ticks' => ['stepSize' => 1], // Agar angka sumbu X bulat (1, 2, 3), bukan desimal
                    'grid' => ['display' => true, 'borderDash' => [2, 2]],
                ],
                'y' => [
                    'grid' => ['display' => false],
                ],
            ],
        ];
    }
}