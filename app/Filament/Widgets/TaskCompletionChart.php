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

    // --- 1. JUDUL DINAMIS (Memberi info status filter) ---
    public function getHeading(): ?string
    {
        $user = Auth::user();
        if ($user->hasRole('pengawas')) {
            $filters = $this->filters;
            // Jika Sub Unit belum dipilih, judulnya memberi petunjuk
            if (empty($filters['subunit_id'])) {
                return 'Menunggu Filter Sub Unit...';
            }
        }
        return 'Statistik Pengerjaan Tugas';
    }

    // --- 2. DESKRIPSI (Instruksi Jelas) ---
    public function getDescription(): ?string
    {
        $user = Auth::user();
        if ($user->hasRole('pengawas')) {
            $filters = $this->filters;
            // Jika Sub Unit belum dipilih, beri instruksi
            if (empty($filters['subunit_id'])) {
                return 'Mohon pilih Direktorat > Unit > Sub Unit untuk menampilkan data.';
            }
        }
        return null;
    }

    protected function getData(): array
    {
        $user = Auth::user();
        $filters = $this->filters;

        $startDate = $filters['startDate'] ?? now()->startOfMonth();
        $endDate = $filters['endDate'] ?? now()->endOfMonth();

        // ==========================================
        // LOGIKA PEGAWAI (Tetap Sama / Tidak Berubah)
        // ==========================================
        if (! $user->hasRole('pengawas')) {
            $data = Trend::query(Task::query()->where('user_id', $user->id))
                ->between(
                    start: Carbon::parse($startDate),
                    end: Carbon::parse($endDate),
                )
                ->perDay()
                ->count();
            
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
        
        // SYARAT: Jika Sub Unit KOSONG, kembalikan grafik KOSONG.
        // (Tidak peduli apakah Direktorat atau Unit sudah dipilih atau belum)
        if (empty($filters['subunit_id'])) {
            return [
                'datasets' => [],
                'labels' => [],
            ];
        }

        // Jika sampai sini, berarti Sub Unit SUDAH dipilih.
        // Jalankan query khusus untuk Sub Unit tersebut.
        $employees = User::query()
            ->whereHas('roles', fn($q) => $q->where('name', 'pegawai'))
            ->where('subunit_id', $filters['subunit_id']) // Filter Sub Unit
            ->withCount(['tasks' => function ($query) use ($startDate, $endDate) {
                $query->whereBetween('created_at', [$startDate, $endDate]);
            }])
            ->orderByDesc('tasks_count')
            ->limit(20)
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Total Tugas Selesai',
                    'data' => $employees->pluck('tasks_count'),
                    'backgroundColor' => '#3b82f6',
                    'borderColor' => '#2563eb',
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