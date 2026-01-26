<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Task;
use Illuminate\Support\Facades\Auth;
use Filament\Support\RawJs;

class TaskStatusChart extends ChartWidget
{
    protected static ?int $sort = 2;
    protected static bool $isLazy = true;
    protected static ?string $maxHeight = '300px';
    protected int | string | array $columnSpan = 1;

    // Judul Dinamis dengan Total
    public function getHeading(): ?string
    {
        $user = Auth::user();
        $total = Task::where('user_id', $user->id)->count();

        return "Status Tugas (Total: $total)";
    }

    // Hanya tampil untuk Pegawai
    public static function canView(): bool
    {
        return Auth::user() && Auth::user()->hasRole('pegawai');
    }

    protected function getData(): array
    {
        $user = Auth::user();

        // Ambil data dari database
        $dataRaw = Task::query()
            ->where('user_id', $user->id)
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // Mapping Data agar urutan warna sesuai
        $counts = [
            'Batal'   => $dataRaw['Batal'] ?? $dataRaw['canceled'] ?? 0,
            'Belum'   => $dataRaw['Belum'] ?? $dataRaw['pending'] ?? 0,
            'Sedang'  => $dataRaw['Sedang'] ?? $dataRaw['in_progress'] ?? 0,
            'Selesai' => $dataRaw['Selesai'] ?? $dataRaw['completed'] ?? 0,
        ];

        return [
            'datasets' => [
                [
                    'label' => 'Jumlah Tugas',
                    'data' => array_values($counts),
                    'backgroundColor' => ['#fbbf24', '#818cf8', '#f87171', '#22d3ee'],
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