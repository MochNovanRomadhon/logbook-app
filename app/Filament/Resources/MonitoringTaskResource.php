<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MonitoringTaskResource\Pages;
use App\Models\Task;
use App\Models\Directorate;
use App\Models\Unit;
use App\Models\Subunit;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;

// Import Infolist
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Section as InfoSection;
use Filament\Infolists\Components\Grid as InfoGrid;
use Filament\Infolists\Components\TextEntry\TextEntrySize;

class MonitoringTaskResource extends Resource
{
    protected static ?string $model = Task::class;
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationGroup = 'Monitoring';
    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Monitoring Tugas';
    protected static ?string $pluralModelLabel = 'Monitoring Tugas';
    protected static ?string $navigationLabel = 'Monitoring Tugas';

    public static function shouldRegisterNavigation(): bool
    {
        return Auth::user()->hasRole(['super_admin', 'pengawas']);
    }

    // Tidak boleh Create
    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        $isAdminOrPengawas = Auth::user()->hasRole(['super_admin', 'pengawas']);

        return $form
            ->schema([
                Section::make()
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Judul Tugas')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull()
                            ->disabled($isAdminOrPengawas),
                        
                        Grid::make(2)->schema([
                            Forms\Components\Select::make('urgency')
                                ->label('Tingkat Urgensi')
                                ->options([
                                    1 => '1. Rendah', 2 => '2. Normal', 3 => '3. Tinggi', 
                                    4 => '4. Sangat Tinggi', 5 => '5. Urgent'
                                ])
                                ->required()
                                ->default(2)
                                ->disabled($isAdminOrPengawas),

                            Forms\Components\DatePicker::make('deadline')
                                ->label('Tenggat Waktu')
                                ->required()
                                ->disabled($isAdminOrPengawas),
                            
                            Forms\Components\Select::make('status')
                                ->label('Status')
                                ->options([
                                    'pending' => 'Pending',
                                    'in_progress' => 'Sedang Dikerjakan',
                                    'completed' => 'Selesai',
                                    'cancelled' => 'Batal'
                                ])
                                ->default('pending')
                                ->required()
                                ->disabled($isAdminOrPengawas),
                        ]),

                        Forms\Components\Textarea::make('description')
                            ->label('Deskripsi')
                            ->rows(3)
                            ->columnSpanFull()
                            ->disabled($isAdminOrPengawas),
                    ])
            ]);
    }

    public static function table(Table $table): Table
{
    return $table
        ->recordAction(Tables\Actions\ViewAction::class) 
        ->recordUrl(null) 
        
        ->modifyQueryUsing(function (Builder $query, $livewire) {
            $query->with(['user.subunit.unit.directorate', 'assigner']);

            if (!Auth::user()->hasRole(['super_admin', 'pengawas'])) {
                return $query->where('user_id', Auth::id());
            }

            $filters = $livewire->tableFilters;
            $hasSearchFilter = false;

            if ($filters) {
                $hasSearchFilter = !empty($filters['user_id']['value']) || 
                                   !empty($filters['location']['directorate_id']) || 
                                   !empty($filters['location']['unit_id']) || 
                                   !empty($filters['location']['subunit_id']);
            }

            if (!$hasSearchFilter) {
                return $query->whereRaw('1 = 0');
            }

            return $query;
        })
        ->defaultSort('deadline', 'asc')
        ->emptyStateHeading('Belum ada data ditampilkan')
        ->emptyStateDescription('Gunakan filter Direktorat, Unit, atau Pegawai untuk menampilkan data.')
        ->emptyStateIcon('heroicon-o-magnifying-glass')
        ->contentFooter(fn() => view('filament.components.urgency-legend'))
        
        ->columns([
            Tables\Columns\TextColumn::make('title')->label('Judul')->limit(30),
            
            Tables\Columns\TextColumn::make('user.subunit.name')->label('Sub Unit')->sortable()
                ->visible(fn() => Auth::user()->hasRole(['super_admin', 'pengawas'])),

            Tables\Columns\TextColumn::make('urgency')
                ->label('Urgensi')
                ->badge()
                ->formatStateUsing(fn(string $state): string => match($state) {
                    '5' => '4',
                    '4' => '4',
                    default => $state,
                })
                ->color(fn (string $state): string => match ($state) {
                    '1' => 'gray', '2' => 'info', '3' => 'warning', '4' => 'danger', '5' => 'danger', default => 'gray',
                }),
                
            Tables\Columns\TextColumn::make('deadline')->date('d M Y')->sortable()
                ->color(fn ($record) => $record->deadline < now() && !in_array($record->status, ['completed', 'cancelled']) ? 'danger' : 'gray')
                ->description(function (\App\Models\Task $record): ?string {
                    if (in_array($record->status, ['completed', 'cancelled'])) return null;
                    
                    $now = now()->startOfDay();
                    $deadline = \Carbon\Carbon::parse($record->deadline)->startOfDay();
                    
                    if ($deadline->isBefore($now)) {
                        return 'Terlambat';
                    }
                    
                    $diffDays = $now->diffInDays($deadline); 
                    if ($diffDays <= 3) {
                        return $diffDays == 0 ? 'Hari ini' : "H-{$diffDays}";
                    }
                    
                    return null;
                }),

            // Kolom Ditugaskan Oleh
            Tables\Columns\TextColumn::make('assigner.name')
                ->label('Ditugaskan Oleh')
                ->placeholder('Inisiatif Sendiri')
                ->toggleable(isToggledHiddenByDefault: true),
                
            // Status Interaktif
            Tables\Columns\SelectColumn::make('status')
                ->label('Status')
                ->options([
                    'pending' => 'Menunggu',
                    'in_progress' => 'Sedang Proses',
                    'completed' => 'Selesai',
                    'cancelled' => 'Batal',
                ])
                ->beforeStateUpdated(function ($record, $state) {
                    if ($state === 'completed') {
                        $record->completed_at = now();
                    } elseif ($state === 'cancelled') {
                        $record->cancelled_at = now();
                    }
                })
                ->searchable()
                ->sortable(),
        ])
        ->filters([
            // --- LOKASI KASCADING ---
            Tables\Filters\Filter::make('location')
                ->form([
                    \Filament\Forms\Components\Grid::make(3)->schema([
                        \Filament\Forms\Components\Select::make('directorate_id')
                            ->label('Direktorat')
                            ->placeholder('Pilih Direktorat')
                            ->options(\App\Models\Directorate::where('is_active', true)->pluck('name', 'id'))
                            ->live()
                            ->afterStateUpdated(fn (\Filament\Forms\Set $set) => $set('unit_id', null)),
                        \Filament\Forms\Components\Select::make('unit_id')
                            ->label('Unit')
                            ->placeholder('Pilih Unit')
                            ->options(fn (\Filament\Forms\Get $get) => \App\Models\Unit::where('directorate_id', $get('directorate_id'))->where('is_active', true)->pluck('name', 'id'))
                            ->live()
                            ->afterStateUpdated(fn (\Filament\Forms\Set $set) => $set('subunit_id', null)),
                        \Filament\Forms\Components\Select::make('subunit_id')
                            ->label('Sub Unit')
                            ->placeholder('Pilih Sub Unit')
                            ->options(fn (\Filament\Forms\Get $get) => \App\Models\Subunit::where('unit_id', $get('unit_id'))->where('is_active', true)->pluck('name', 'id')),
                    ])
                ])
                ->query(function (Builder $query, array $data): Builder {
                    return $query
                        ->when($data['directorate_id'], fn ($q, $v) => $q->whereHas('user.subunit.unit', fn ($subQ) => $subQ->where('directorate_id', $v)))
                        ->when($data['unit_id'], fn ($q, $v) => $q->whereHas('user.subunit', fn ($subQ) => $subQ->where('unit_id', $v)))
                        ->when($data['subunit_id'], fn ($q, $v) => $q->whereHas('user', fn ($subQ) => $subQ->where('subunit_id', $v)));
                })
                ->visible(fn() => Auth::user()->hasRole(['super_admin', 'pengawas']))
                ->columnSpan(12),

            // --- BARIS 2 (3 Input + 1 Tombol) ---
            Tables\Filters\SelectFilter::make('user_id')
                ->label('Pegawai')
                ->placeholder('Pilih Pegawai')
                ->relationship('user', 'name', fn(\Illuminate\Database\Eloquent\Builder $query) => $query->where('is_active', true))
                ->searchable()
                ->visible(fn() => Auth::user()->hasRole(['super_admin', 'pengawas']))
                ->columnSpan(3),

            Tables\Filters\SelectFilter::make('status')
                ->placeholder('Pilih Status')
                ->options(['pending'=>'Pending', 'in_progress'=>'Proses', 'completed'=>'Selesai', 'cancelled'=>'Batal'])
                ->columnSpan(3),

            Tables\Filters\SelectFilter::make('urgency')
                ->placeholder('Pilih Urgensi')
                ->options([1 => '1. Rendah', 2 => '2. Normal', 3 => '3. Tinggi', 5 => '4. Urgent'])
                ->columnSpan(3),

            // --- TOMBOL CUSTOM ---
            Tables\Filters\Filter::make('search_trigger')
                ->label('') 
                ->form([
                    \Filament\Forms\Components\ViewField::make('search_btn')
                        ->view('filament.components.filter-button')
                        ->label('')
                ])
                ->columnSpan(3),

        ], layout: FiltersLayout::AboveContent)
        
        ->filtersFormColumns(12) 
        ->deferFilters()
        
        // Sembunyikan tombol native
        ->filtersApplyAction(fn (\Filament\Tables\Actions\Action $action) => $action->hidden())

        ->actions([
            Tables\Actions\Action::make('add_note')
                ->label('Catatan')
                ->icon('heroicon-o-document-text')
                ->color('secondary')
                ->form([
                    \Filament\Forms\Components\Textarea::make('notes')
                        ->label('Catatan Tambahan')
                        ->rows(3)
                        ->default(fn (\App\Models\Task $record) => $record->notes),
                ])
                ->action(function (\App\Models\Task $record, array $data): void {
                    $record->update(['notes' => $data['notes']]);
                }),

            Tables\Actions\ViewAction::make()
                ->label('Lihat Detail')
                ->modalHeading('Rincian Tugas'),
        ])
        ->bulkActions([
            Tables\Actions\BulkActionGroup::make([
                Tables\Actions\BulkAction::make('update_status')
                    ->label('Perbarui Status')
                    ->icon('heroicon-o-pencil-square')
                    ->color('primary')
                    ->form([
                        \Filament\Forms\Components\Select::make('status')
                            ->label('Pilih Status Baru')
                            ->options([
                                'pending' => 'Menunggu',
                                'in_progress' => 'Sedang Proses',
                                'completed' => 'Selesai',
                                'cancelled' => 'Batal',
                            ])
                            ->required(),
                    ])
                    ->action(function (\Illuminate\Database\Eloquent\Collection $records, array $data): void {
                        foreach ($records as $record) {
                            $updateData = ['status' => $data['status']];
                            if ($data['status'] === 'completed') {
                                $updateData['completed_at'] = now();
                            } elseif ($data['status'] === 'cancelled') {
                                $updateData['cancelled_at'] = now();
                            }
                            $record->update($updateData);
                        }
                    })
                    ->deselectRecordsAfterCompletion(),
            ]),
        ]);
}

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                InfoSection::make('Informasi Utama')
                    ->schema([
                        // Baris 1: Judul
                        TextEntry::make('title')
                            ->label('Judul Tugas')
                            ->weight('bold')
                            ->size(TextEntrySize::Large)
                            ->columnSpanFull(),

                        // Baris 2: Deskripsi
                        TextEntry::make('description')
                            ->label('Deskripsi Tugas')
                            ->markdown()
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ]),
                
                InfoSection::make('Detail Tambahan')
                    ->schema([
                        InfoGrid::make(3)->schema([
                            TextEntry::make('urgency')
                                ->label('Tingkat Urgensi')
                                ->badge()
                                ->formatStateUsing(fn (string $state): string => match ($state) {
                                    '1' => '1. Rendah', '2' => '2. Normal', '3' => '3. Tinggi',
                                    '4' => '4. Urgent', '5' => '4. Urgent', default => $state,
                                })
                                ->color(fn (string $state): string => match ($state) {
                                    '1' => 'gray', '2' => 'info', '3' => 'warning', '4' => 'danger', '5' => 'danger', default => 'gray',
                                }),

                            TextEntry::make('deadline')
                                ->label('Tenggat Waktu')
                                ->date('d F Y'),
                                
                            TextEntry::make('assigner.name')
                                ->label('Ditugaskan Oleh')
                                ->badge()
                                ->color('gray')
                                ->placeholder('Inisiatif Sendiri'),
                                
                            TextEntry::make('user.subunit.name')
                                ->label('Pemilik Tugas (Sub Unit)')
                                ->visible(fn() => Auth::user()->hasRole(['super_admin', 'pengawas'])),
                        ]),

                        TextEntry::make('notes')
                            ->label('Catatan Tambahan')
                            ->markdown()
                            ->placeholder('Tidak ada catatan.')
                            ->columnSpanFull(),
                    ]),

                InfoSection::make('Status & Riwayat Waktu')
                    ->schema([
                        InfoGrid::make(4)->schema([
                            TextEntry::make('status')
                                ->label('Status Saat Ini')
                                ->badge()
                                ->formatStateUsing(fn (string $state): string => match ($state) {
                                    'pending' => 'Menunggu',
                                    'in_progress' => 'Sedang Diproses',
                                    'completed' => 'Selesai',
                                    'cancelled' => 'Batal',
                                    default => $state,
                                })
                                ->colors([
                                    'gray' => 'pending', 'info' => 'in_progress', 'success' => 'completed', 'danger' => 'cancelled',
                                ]),

                            TextEntry::make('created_at')
                                ->label('Dibuat Pada')
                                ->date('d F Y')
                                ->placeholder('-'),
                                
                            TextEntry::make('processed_at')
                                ->label('Mulai Dikerjakan')
                                ->date('d F Y')
                                ->placeholder('-'),
                            
                            TextEntry::make('completed_at')
                                ->label('Selesai / Dibatalkan')
                                ->getStateUsing(fn(\App\Models\Task $record) => $record->completed_at ?? $record->cancelled_at)
                                ->date('d F Y H:i')
                                ->placeholder('-'),
                        ]),
                    ])->collapsed(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMonitoringTasks::route('/'),
        ];
    }
}