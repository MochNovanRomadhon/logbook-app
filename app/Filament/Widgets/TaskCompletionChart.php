<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class TaskCompletionChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?string $heading = 'Statistik Tugas';
    protected static bool $isLazy = true;
    protected int | string | array $columnSpan = 'full';
    protected static ?string $maxHeight = '300px';
    protected static ?int $sort = 1;

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
        return 'Statistik Tugas';
    }

    // --- 2. DESKRIPSI (Instruksi Jelas) ---
    public function getDescription(): ?string
    {
        $user = Auth::user();
        if ($user->hasRole('pengawas')) {
            return 'Jumlah tugas pada unit kerja berdasarkan filter tanggal';
        }
        return 'Grafik tren jumlah tugas harian berdasarkan tenggat waktu';
    }

    // --- 3. FILTER STATUS TASK ---
    public ?string $filter = 'completed'; // Default filter

    protected function getFilters(): ?array
    {
        return [
            '' => 'Semua Status',
            'pending' => 'Menunggu',
            'in_progress' => 'Proses',
            'completed' => 'Selesai',
            'cancelled' => 'Batal',
        ];
    }

    protected function getData(): array
    {
        $user = Auth::user();
        $filters = $this->filters;

        $startDate = $filters['startDate'] ?? now()->startOfWeek();
        $endDate = $filters['endDate'] ?? now()->endOfWeek();

        // ==========================================
        // LOGIKA PEGAWAI (Tetap Sama / Tidak Berubah)
        // ==========================================
        if (! $user->hasRole('pengawas')) {
            $taskQuery = Task::query()->where('user_id', $user->id);
            
            if (!empty($this->filter)) {
                $taskQuery->where('status', $this->filter);
            }

            // --- Terapkan Cache 15 Menit ---
            $cacheKey = "task_completion_pegawai_{$user->id}_{$startDate}_{$endDate}_{$this->filter}";
            
            $data = Cache::remember($cacheKey, now()->addMinutes(15), function () use ($taskQuery, $startDate, $endDate) {
                return Trend::query($taskQuery)
                    ->dateColumn('deadline')
                    ->between(
                        start: Carbon::parse($startDate)->startOfDay(),
                        end: Carbon::parse($endDate)->endOfDay(),
                    )
                    ->perDay()
                    ->count();
            });
            
            return [
                'datasets' => [
                    [
                        'label' => 'Jumlah Tugas Harian',
                        'data' => $data->map(fn (TrendValue $value) => $value->aggregate),
                        'borderColor' => '#6366f1',
                        'backgroundColor' => ['rgba(99, 102, 241, 0.3)'],
                        'fill' => [
                            'target' => 'origin',
                            'above' => 'rgba(99, 102, 241, 0.15)',
                        ],
                        'tension' => 0.4,
                        'pointRadius' => 5,
                        'pointHoverRadius' => 8,
                        'pointBackgroundColor' => '#6366f1',
                        'pointBorderColor' => '#ffffff',
                        'pointBorderWidth' => 2,
                        'pointHoverBackgroundColor' => '#4f46e5',
                        'pointHoverBorderColor' => '#ffffff',
                        'pointHoverBorderWidth' => 3,
                        'borderWidth' => 3,
                        'borderCapStyle' => 'round',
                        'borderJoinStyle' => 'round',
                    ],
                ],
                'labels' => $data->map(fn (TrendValue $value) => Carbon::parse($value->date)->format('d M')),
            ];
        }

        // ==========================================
        // LOGIKA PENGAWAS (UBAHAN DISINI)
        // ==========================================
        
        $directorateId = $filters['directorate_id'] ?? null;
        $unitId = $filters['unit_id'] ?? null;
        $subunitId = $filters['subunit_id'] ?? null;

        // Auto-fill from pengawas scope if filters are empty
        if (empty($directorateId) && empty($unitId) && empty($subunitId)) {
            if ($user->subunit_id) {
                $subunitId = $user->subunit_id;
            } elseif ($user->unit_id) {
                $unitId = $user->unit_id;
            } elseif ($user->directorate_id) {
                $directorateId = $user->directorate_id;
            }
        }

        if (empty($directorateId) && empty($unitId) && empty($subunitId)) {
            return [
                'datasets' => [],
                'labels' => [],
            ];
        }

        // Ambil status dari filter chart (bukan filter global)
        $statusFilter = $this->filter;

        // Tentukan Label Grafik berdasarkan status
        $chartLabel = match ($statusFilter) {
            'pending' => 'Total Tugas Menunggu',
            'in_progress' => 'Total Tugas Proses',
            'completed' => 'Total Tugas Selesai',
            'cancelled' => 'Total Tugas Batal',
            default => 'Total Semua Tugas',
        };

        // Tentukan Warna Grafik berdasarkan status
        $colors = match ($statusFilter) {
            'pending' => [
                'bg' => '#f59e0b', // Amber-500
                'border' => '#d97706', // Amber-600
            ],
            'in_progress' => [
                'bg' => '#3b82f6', // Blue-500
                'border' => '#2563eb', // Blue-600
            ],
            'completed' => [
                'bg' => '#10b981', // Emerald-500
                'border' => '#059669', // Emerald-600
            ],
            'cancelled' => [
                'bg' => '#ef4444', // Red-500
                'border' => '#dc2626', // Red-600
            ],
            default => [ // Jika "Semua Status"
                'bg' => '#6366f1', // Indigo-500
                'border' => '#4f46e5', // Indigo-600
            ],
        };

        $cacheKey = "task_completion_pengawas_{$user->id}_{$directorateId}_{$unitId}_{$subunitId}_{$startDate}_{$endDate}_{$statusFilter}";

        $employees = Cache::remember($cacheKey, now()->addMinutes(15), function () use ($directorateId, $unitId, $subunitId, $startDate, $endDate, $statusFilter) {
            $query = User::query()
                ->whereHas('roles', fn($q) => $q->whereIn('name', ['pegawai', 'pengawas']));

            if (!empty($subunitId)) {
                $query->where('subunit_id', $subunitId);
            } elseif (!empty($unitId)) {
                $query->where('unit_id', $unitId);
            } elseif (!empty($directorateId)) {
                $query->where('directorate_id', $directorateId);
            }

            return $query->withCount(['tasks' => function ($q) use ($startDate, $endDate, $statusFilter) {
                    $q->whereBetween('deadline', [$startDate, $endDate]);
                    if (! empty($statusFilter)) {
                        $q->where('status', $statusFilter);
                    }
                }])
                ->orderByDesc('tasks_count')
                ->limit(20)
                ->get();
        });

        return [
            'datasets' => [
                [
                    'label' => $chartLabel,
                    'data' => $employees->pluck('tasks_count'),
                    'backgroundColor' => $colors['bg'],
                    'borderColor' => $colors['border'],
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