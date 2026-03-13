<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use App\Models\Task;
use Illuminate\Support\Facades\Auth;
use Filament\Support\RawJs;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class TaskStatusChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 2;
    protected static bool $isLazy = true;
    protected static ?string $maxHeight = '300px';
    protected int | string | array $columnSpan = 1;

    // Judul Dinamis dengan Total
    public function getHeading(): ?string
    {
        $user = Auth::user();
        $filters = $this->filters;

        $startDate = Carbon::parse($filters['startDate'] ?? now()->startOfWeek())->startOfDay();
        $endDate = Carbon::parse($filters['endDate'] ?? now()->endOfWeek())->endOfDay();

        $total = Task::where('user_id', $user->id)
            ->whereBetween('deadline', [$startDate, $endDate])
            ->count();

        return "Status Tugas (Total: $total)";
    }

    public function getDescription(): ?string
    {
        return 'Komposisi status tugas berdasarkan rentang waktu';
    }

    // Hanya tampil untuk Pegawai
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

        $cacheKey = "task_status_{$user->id}_{$startDate->format('Y-m-d')}_{$endDate->format('Y-m-d')}";

        // Ambil data dari database dengan filter tanggal dan cache
        $dataRaw = Cache::remember($cacheKey, now()->addMinutes(15), function () use ($user, $startDate, $endDate) {
            return Task::query()
                ->where('user_id', $user->id)
                ->whereBetween('deadline', [$startDate, $endDate])
                ->selectRaw('status, count(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray();
        });

        // Mapping Data agar urutan warna sesuai
        $counts = [
            'Menunggu' => $dataRaw['pending'] ?? 0,
            'Proses'   => $dataRaw['in_progress'] ?? 0,
            'Selesai'  => $dataRaw['completed'] ?? 0,
            'Batal'    => $dataRaw['cancelled'] ?? 0,
        ];

        return [
            'datasets' => [
                [
                    'label' => 'Jumlah Tugas',
                    'data' => array_values($counts),
                    'backgroundColor' => ['#f59e0b', '#3b82f6', '#10b981', '#ef4444'],
                    'borderColor' => '#ffffff',
                    'borderWidth' => 2,
                    'hoverOffset' => 10, // Efek membesar sedikit saat di-hover
                ],
            ],
            'labels' => array_keys($counts),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'cutout' => '60%', // Ukuran lubang tengah
            'responsive' => true,
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                    'labels' => [
                        'usePointStyle' => true, // Memakai titik bulat di legend
                        'padding' => 20,
                    ],
                ],
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
                ],
            ],
            'onClick' => RawJs::make(<<<'JS'
                function(evt, elements) {
                    if (elements.length > 0) {
                        window.location.href = '/admin/tasks'; 
                    }
                }
            JS),
            // MENGHILANGKAN GARIS GRID (Sumbu X dan Y dimatikan total)
            'scales' => [
                'x' => ['display' => false],
                'y' => ['display' => false],
            ],
        ];
    }
}