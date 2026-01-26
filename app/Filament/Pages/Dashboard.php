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

class Dashboard extends BaseDashboard
{
    use HasFiltersForm;

    protected static ?string $navigationLabel = 'Dasbor';
    protected static ?string $title = 'Dasbor';

    public ?array $data = [];
    public bool $hasSearched = false;

    public function mount(): void
    {
        $this->resetFilters(); // Set default saat awal load
    }

    // Fungsi khusus untuk mereset filter ke default
    public function resetFilters(): void
    {
        $this->data = [
            'startDate' => now()->startOfMonth(),
            'endDate' => now()->endOfMonth(),
            'directorate_id' => null,
            'unit_id' => null,
            'subunit_id' => null,
        ];
        $this->filters = []; // Kosongkan filter aktif
        $this->hasSearched = false; // Kembali ke mode instruksi
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
                        ->action(fn () => $this->resetFilters())
                ])
                ->schema([
                    // 1. FILTER HIERARKI
                    Group::make()
                        ->schema([
                            Grid::make(3)->schema([
                                Select::make('directorate_id')
                                    ->label('Direktorat')
                                    ->options(Directorate::pluck('name', 'id'))
                                    ->searchable()
                                    ->live() 
                                    ->disabled(fn (Get $get) => filled($get('unit_id')))
                                    ->afterStateUpdated(fn (callable $set) => $set('unit_id', null)),

                                Select::make('unit_id')
                                    ->label('Unit')
                                    ->options(fn (Get $get) => Unit::where('directorate_id', $get('directorate_id'))->pluck('name', 'id'))
                                    ->searchable()
                                    ->live()
                                    ->disabled(fn (Get $get) => filled($get('subunit_id')) || blank($get('directorate_id')))
                                    ->afterStateUpdated(fn (callable $set) => $set('subunit_id', null)),

                                Select::make('subunit_id')
                                    ->label('Sub Unit')
                                    ->options(fn (Get $get) => Subunit::where('unit_id', $get('unit_id'))->pluck('name', 'id'))
                                    ->searchable()
                                    ->disabled(fn (Get $get) => blank($get('unit_id'))),
                            ]),
                        ])
                        ->visible($isPengawas),

                   // 2. FILTER TANGGAL & TOMBOL CARI
// 2. FILTER TANGGAL & TOMBOL CARI
Grid::make(3)
    ->schema([
        DatePicker::make('startDate')
            ->label('Tanggal Awal'),
            // ->required(),
        
        DatePicker::make('endDate')
            ->label('Tanggal Akhir'),
            // ->required(),

        // --- SOLUSI AMPUH: MANUAL MARGIN ---
        // 1. Jangan pakai Group.
        // 2. Jangan pakai Placeholder/Label Palsu.
        // 3. Langsung Actions.
        Actions::make([
            Action::make('filter')
                ->label('Cari')
                ->icon('heroicon-m-magnifying-glass')
                ->color('primary')
                ->action(function () {
            // --- TAMBAHAN PENTING ---
            // Validasi dulu input form ($this->data). 
            // Jika kosong/tidak valid, kode di bawahnya BERHENTI dan tidak dijalankan.
            $this->validate(); 

            $this->filters = $this->data;
            $this->hasSearched = true; 
        })
        ])
        ->fullWidth() // Agar tombol melebar penuh
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
        if ($isPengawas && ! $this->hasSearched) {
            return [ DashboardInstructionWidget::class,
                    
            
            ];
        }

        // --- QUERY DATA (Dijalankan jika sudah cari, atau jika user BUKAN pengawas) ---

        $query = Task::query();

        // Terapkan Filter
        if (isset($this->filters['startDate'])) {
            $query->whereDate('created_at', '>=', $this->filters['startDate']);
        }
        if (isset($this->filters['endDate'])) {
            $query->whereDate('created_at', '<=', $this->filters['endDate']);
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
            TaskUrgencyChart::class,
            TaskStatusChart::class,
        ];
    }
}