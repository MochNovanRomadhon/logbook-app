<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Forms\Form;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Get;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Widgets\StatsOverview;   
use App\Filament\Widgets\OrganizationStatsOverview; 

// Model
use App\Models\Directorate;
use App\Models\Unit;
use App\Models\Subunit;
use App\Models\Task; 

// Widgets
use App\Filament\Widgets\DashboardInstructionWidget;
use App\Filament\Widgets\DashboardEmptyDataWidget;
use App\Filament\Widgets\TaskCompletionChart; 
use App\Filament\Widgets\TaskUrgencyChart; 
use App\Filament\Widgets\TaskStatusChart; 
use App\Filament\Widgets\LogbookWorkChart;

class Dashboard extends BaseDashboard
{
    use HasFiltersForm;

    protected static ?string $navigationLabel = 'Dashboard';
    protected static ?string $title = 'Dashboard';

    public ?array $data = [];
    public bool $hasSearched = false;

    public function mount(): void
    {
        $user = Auth::user();
        
        // Default date range
        $this->data = [
            'startDate' => now()->startOfWeek(),
            'endDate' => now()->endOfWeek(),
            'directorate_id' => null,
            'unit_id' => null,
            'subunit_id' => null,
        ];
        
        // Auto-set filters for Pengawas based on their org scope
        if ($user->hasRole('pengawas')) {
            if ($user->subunit_id) {
                $this->data['subunit_id'] = $user->subunit_id;
                $this->data['unit_id'] = $user->subunit?->unit_id;
                $this->data['directorate_id'] = $user->subunit?->unit?->directorate_id;
            } elseif ($user->unit_id) {
                $this->data['unit_id'] = $user->unit_id;
                $this->data['directorate_id'] = $user->unit?->directorate_id;
            } elseif ($user->directorate_id) {
                $this->data['directorate_id'] = $user->directorate_id;
            }
            $this->filters = $this->data;
            $this->hasSearched = true;
        } else {
            $this->filters = [];
            $this->hasSearched = false;
        }
    }

public function filtersForm(Form $form): Form
{
    $user = Auth::user();
    $isPengawas = $user->hasRole('pengawas');
    $isSuperAdmin = $user->hasRole('super_admin');

    if ($isSuperAdmin) {
        return $form->schema([]);
    }

    return $form
        ->statePath('data') 
        ->schema([
            Section::make('Filter Dashboard')
                ->headerActions([
                    Action::make('reset')
                        ->label('Atur ulang filter')
                        ->link()
                        ->color('danger')
                        ->action(function () {
                            $user = Auth::user();
                            $this->data['startDate'] = now()->startOfWeek();
                            $this->data['endDate'] = now()->endOfWeek();
                            if ($user->hasRole('pengawas')) {
                                // Reset date only, keep org scope
                                $this->filters = $this->data;
                                $this->hasSearched = true;
                            } else {
                                $this->filters = [];
                                $this->hasSearched = false;
                            }
                        })
                ])
                ->schema([
                   // FILTER TANGGAL & TOMBOL CARI
                    Grid::make(3)
                        ->schema([
                            DatePicker::make('startDate')
                                ->label('Tanggal Awal'),
                            
                            DatePicker::make('endDate')
                                ->label('Tanggal Akhir'),

                            Actions::make([
                                Action::make('filter')
                                    ->label('Cari')
                                    ->icon('heroicon-m-magnifying-glass')
                                    ->color('primary')
                                    ->action(function () {
                                        $this->validate(); 
                                        $this->filters = $this->data;
                                        $this->hasSearched = true; 
                                    })
                            ])
                            ->fullWidth()
                            ->extraAttributes([
                                'style' => 'margin-top: 32px'
                            ]), 
                        ]),
                ])
                ->collapsible()
        ]);
}

    public function getWidgets(): array
    {
        $user = auth()->user(); // Gunakan helper auth() agar lebih aman dari error import

        // --- LOGIKA PENENTU WIDGET ---
        if ($user->hasRole('super_admin')) {
            return [
                StatsOverview::class,
                OrganizationStatsOverview::class     // Widget Kotak Angka (Role & User)
            ];
        }
        // Cek 1: Apakah user PENGAWAS? (Pastikan nama role persis 'pengawas' huruf kecil)
        $isPengawas = $user->hasRole('pengawas');

        // Cek 2: Jika dia Pengawas DAN Belum Klik Cari, WAJIB munculkan instruksi.
        // (Untuk pengawas dengan auto-display, hasSearched sudah true dari mount)
        if ($isPengawas && ! $this->hasSearched) {
            return [ DashboardInstructionWidget::class ];
        }

        // --- QUERY DATA (Dijalankan jika sudah cari, atau jika user BUKAN pengawas) ---

        $query = Task::query();

        // Terapkan Filter
        if (isset($this->filters['startDate'])) {
            $query->whereDate('deadline', '>=', $this->filters['startDate']);
        }
        if (isset($this->filters['endDate'])) {
            $query->whereDate('deadline', '<=', $this->filters['endDate']);
        }

        if (!empty($this->filters['subunit_id'])) {
            $query->whereHas('user', fn (Builder $q) => $q->where('subunit_id', $this->filters['subunit_id']));
        } elseif (!empty($this->filters['unit_id'])) {
            $query->whereHas('user', fn (Builder $q) => $q->where('unit_id', $this->filters['unit_id']));
        } elseif (!empty($this->filters['directorate_id'])) {
            $query->whereHas('user', fn (Builder $q) => $q->where('directorate_id', $this->filters['directorate_id']));
        }

        // Jika data kosong
        if (! $query->exists()) {
            return [ DashboardEmptyDataWidget::class ];
        }

        // Jika data ada
        return [
            TaskCompletionChart::class,
            LogbookWorkChart::class,
            TaskUrgencyChart::class,
            TaskStatusChart::class,
        ];
    }
}