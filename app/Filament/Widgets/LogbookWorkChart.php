<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use App\Models\Logbook;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class LogbookWorkChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?string $heading = 'Statistik Pengerjaan';
    protected static bool $isLazy = true;
    protected int | string | array $columnSpan = 'full';
    protected static ?string $maxHeight = '300px';
    protected static ?int $sort = 2;

    public static function canView(): bool
    {
        return !Auth::user()->hasRole('super_admin');
    }

    protected function getType(): string
    {
        return Auth::user()->hasRole('pengawas') ? 'bar' : 'line';
    }

    public function getHeading(): ?string
    {
        $user = Auth::user();
        if ($user->hasRole('pengawas')) {
            $filters = $this->filters;
            if (empty($filters['subunit_id'])) {
                return 'Menunggu Filter Sub Unit...';
            }
        }
        return 'Statistik Pengerjaan';
    }

    public function getDescription(): ?string
    {
        $user = Auth::user();
        if ($user->hasRole('pengawas')) {
            $filters = $this->filters;
            if (empty($filters['subunit_id'])) {
                return 'Mohon pilih Direktorat > Unit > Sub Unit untuk menampilkan data.';
            }
        }
        return 'Jumlah pengerjaan tugas berdasarkan logbook per hari';
    }

    protected function getData(): array
    {
        $user = Auth::user();
        $filters = $this->filters;

        $startDate = Carbon::parse($filters['startDate'] ?? now()->startOfWeek())->startOfDay();
        $endDate = Carbon::parse($filters['endDate'] ?? now()->endOfWeek())->endOfDay();

        // ==========================================
        // LOGIKA PEGAWAI
        // ==========================================
        if (! $user->hasRole('pengawas')) {
            $cacheKey = "logbook_work_pegawai_{$user->id}_{$startDate->format('Y-m-d')}_{$endDate->format('Y-m-d')}";
            
            $logbooks = Cache::remember($cacheKey, now()->addMinutes(15), function () use ($user, $startDate, $endDate) {
                return Logbook::withCount('items')
                    ->where('user_id', $user->id)
                    ->where('is_submitted', true)
                    ->whereBetween('date', [$startDate, $endDate])
                    ->get()
                    ->keyBy(fn ($l) => Carbon::parse($l->date)->format('Y-m-d'));
            });

            $period = \Carbon\CarbonPeriod::create($startDate, $endDate);
            
            $labels = [];
            $dataPoints = [];
            
            foreach ($period as $date) {
                $dateString = $date->format('Y-m-d');
                $labels[] = $date->format('d M');
                $dataPoints[] = isset($logbooks[$dateString]) ? $logbooks[$dateString]->items_count : 0;
            }

            return [
                'datasets' => [
                    [
                        'label' => 'Total Pengerjaan Tugas',
                        'data' => $dataPoints,
                        'borderColor' => '#10b981',
                        'backgroundColor' => ['rgba(16, 185, 129, 0.3)'],
                        'fill' => [
                            'target' => 'origin',
                            'above' => 'rgba(16, 185, 129, 0.15)',
                        ],
                        'tension' => 0.4,
                        'pointRadius' => 5,
                        'pointHoverRadius' => 8,
                        'pointBackgroundColor' => '#10b981',
                        'pointBorderColor' => '#ffffff',
                        'pointBorderWidth' => 2,
                        'pointHoverBackgroundColor' => '#059669',
                        'pointHoverBorderColor' => '#ffffff',
                        'pointHoverBorderWidth' => 3,
                        'borderWidth' => 3,
                        'borderCapStyle' => 'round',
                        'borderJoinStyle' => 'round',
                    ],
                ],
                'labels' => $labels,
            ];
        }

        // ==========================================
        // LOGIKA PENGAWAS
        // ==========================================
        if (empty($filters['subunit_id'])) {
            return [
                'datasets' => [],
                'labels' => [],
            ];
        }

        $subunitId = $filters['subunit_id'];
        $cacheKey = "logbook_work_pengawas_{$user->id}_{$subunitId}_{$startDate->format('Y-m-d')}_{$endDate->format('Y-m-d')}";

        $employees = Cache::remember($cacheKey, now()->addMinutes(15), function () use ($startDate, $endDate, $subunitId) {
            return User::query()
                ->select('users.*')
                ->selectRaw('(
                    SELECT COUNT(li.id)
                    FROM logbooks l
                    JOIN logbook_items li ON l.id = li.logbook_id
                    WHERE l.user_id = users.id
                    AND l.is_submitted = 1
                    AND l.date BETWEEN ? AND ?
                ) as items_count', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                ->whereHas('roles', fn($q) => $q->where('name', 'pegawai'))
                ->where('subunit_id', $subunitId)
                ->orderByDesc('items_count')
                ->limit(20)
                ->get();
        });

        return [
            'datasets' => [
                [
                    'label' => 'Total Pengerjaan Tugas',
                    'data' => $employees->pluck('items_count'),
                    'backgroundColor' => '#10b981', 
                    'borderColor' => '#059669', 
                    'borderWidth' => 1,
                    'borderRadius' => 4,
                    'barThickness' => 30,
                ],
            ],
            'labels' => $employees->pluck('name'),
        ];
    }

    protected function getOptions(): array
    {
        $isLine = $this->getType() === 'line';

        return [
            'responsive' => true,
            'maintainAspectRatio' => true,
            'plugins' => [
                'legend' => ['display' => false],
                'tooltip' => [
                    'enabled' => true,
                    'backgroundColor' => 'rgba(17, 24, 39, 0.95)',
                    'titleColor' => '#ffffff',
                    'titleFont' => ['size' => 14, 'weight' => 'bold'],
                    'bodyColor' => '#e5e7eb',
                    'bodyFont' => ['size' => 13],
                    'padding' => 12,
                    'cornerRadius' => 8,
                    'displayColors' => true,
                    'intersect' => false,
                    'mode' => 'index',
                ],
            ],
            'interaction' => [
                'intersect' => false,
                'mode' => 'index',
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'stepSize' => 1,
                        'color' => '#9ca3af',
                        'font' => ['size' => 11],
                    ],
                    'grid' => [
                        'display' => true, 
                        'drawBorder' => false,
                        'color' => $isLine ? 'rgba(156, 163, 175, 0.15)' : 'rgba(156, 163, 175, 0.2)',
                        'borderDash' => $isLine ? [5, 5] : [], 
                    ],
                ],
                'x' => [
                    'ticks' => [
                        'color' => '#9ca3af',
                        'font' => ['size' => 11],
                        'maxRotation' => 45,
                    ],
                    'grid' => ['display' => false],
                ],
            ],
            'elements' => [
                'line' => [
                    'tension' => 0.4,
                ],
                'point' => [
                    'hoverRadius' => 8,
                ],
            ],
        ];
    }
}
