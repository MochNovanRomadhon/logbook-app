<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MonitoringLogbookResource\Pages;
use App\Models\Logbook;
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
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Group;
use Filament\Tables\Enums\FiltersLayout;

class MonitoringLogbookResource extends Resource
{
    protected static ?string $model = Logbook::class;
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationLabel = 'Monitoring Logbook';
    protected static ?string $navigationGroup = 'Monitoring';
    protected static ?int $navigationSort = 2;

    public static function shouldRegisterNavigation(): bool
    {
        return Auth::user()->hasRole(['super_admin', 'pengawas']);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Group::make()
                    ->schema([
                        Section::make('Informasi Laporan')
                            ->schema([
                                Grid::make(3)->schema([ 
                                    DatePicker::make('date')
                                        ->label('Tanggal Laporan')
                                        ->default(now())
                                        ->readOnly()
                                        ->required(),
                                    
                                    TextInput::make('status_display')
                                        ->label('Status')
                                        ->disabled()
                                        ->dehydrated(false)
                                        ->formatStateUsing(fn ($record) => $record?->is_submitted ? 'Final (Terkunci)' : 'Draft'),
                                ]),
                                
                                Hidden::make('user_id')->default(fn() => Auth::id()),
                            ]),

                        Section::make('Rincian Aktivitas')
                            ->schema([
                                Repeater::make('items')
                                    ->relationship('items')
                                    ->label('Daftar Pekerjaan')
                                    ->addActionLabel('Tambah Pekerjaan Lain')
                                    ->itemLabel(fn (array $state): ?string => $state['activity'] ?? null)
                                    ->collapsed(false) 
                                    ->schema([
                                        Grid::make(2)->schema([
                                            Select::make('task_id')
                                                ->label('Pilih Tugas')
                                                ->relationship(
                                                    name: 'task', 
                                                    titleAttribute: 'title',
                                                    modifyQueryUsing: fn (Builder $query) => $query
                                                        ->where('user_id', Auth::id())
                                                        ->where('status', '!=', 'completed')
                                                )
                                                ->searchable()
                                                ->preload()
                                                ->required()
                                                ->reactive()
                                                ->afterStateUpdated(function ($state, callable $set) {
                                                    if ($state) {
                                                        $lastLog = \App\Models\LogbookItem::where('task_id', $state)->latest()->first();
                                                        $set('previous_progress', $lastLog ? $lastLog->current_progress : 0);
                                                    }
                                                })
                                                ->columnSpanFull(), 

                                            TextInput::make('previous_progress')
                                                ->label('Progress Awal (%)')
                                                ->numeric()
                                                ->default(0)
                                                ->readOnly()
                                                ->suffix('%'),

                                            TextInput::make('current_progress')
                                                ->label('Progress Akhir (%)')
                                                ->numeric()
                                                ->required()
                                                ->maxValue(100)
                                                ->suffix('%'),

                                            Textarea::make('activity')
                                                ->label('Deskripsi Aktivitas')
                                                ->rows(2) 
                                                ->required()
                                                ->columnSpanFull(),
                                        ])
                                    ])
                            ])
                    ])
                    ->columnSpanFull()
                    ->disabled(fn ($record) => ($record?->is_submitted ?? false) || ($record && $record->user_id !== Auth::id()))
            ]);
    }

public static function table(Table $table): Table
{
    return $table
        ->modifyQueryUsing(function (Builder $query, $livewire) {
            $query->with(['user.subunit.unit.directorate']);
            
            if (!Auth::user()->hasRole(['super_admin', 'pengawas'])) {
                return $query->where('user_id', Auth::id());
            }

            $filters = $livewire->tableFilters; 
            $hasSearchFilter = false;

            // Cek apakah ada filter yang terisi (termasuk dummy trigger)
            if ($filters) {
                $hasSearchFilter = !empty($filters['user_id']['value']) || 
                                   !empty($filters['directorate']['value']) || 
                                   !empty($filters['unit']['value']) || 
                                   !empty($filters['subunit']['value']);
            }

            if (!$hasSearchFilter) {
                return $query->whereRaw('1 = 0');
            }

            return $query;
        })
        ->emptyStateHeading('Belum ada data ditampilkan')
        ->emptyStateDescription('Gunakan filter di atas untuk menampilkan data.')
        ->emptyStateIcon('heroicon-o-magnifying-glass')

        ->columns([
            Tables\Columns\TextColumn::make('date')->date('d F Y')->label('Tanggal')->sortable(),
            
            Tables\Columns\TextColumn::make('is_submitted')
                ->label('Status')
                ->badge()
                ->formatStateUsing(fn (bool $state) => $state ? 'Final' : 'Draft')
                ->colors(['gray' => false, 'success' => true])
                ->icon(fn (bool $state) => $state ? 'heroicon-o-lock-closed' : 'heroicon-o-pencil'),

            Tables\Columns\TextColumn::make('user.name')
                ->label('Pegawai')
                ->sortable()
                ->visible(fn() => Auth::user()->hasRole(['super_admin', 'pengawas'])),

            Tables\Columns\TextColumn::make('user.subunit.name')->label('Sub Unit')->sortable()->visible(fn() => Auth::user()->hasRole(['super_admin', 'pengawas'])),
            Tables\Columns\TextColumn::make('user.subunit.unit.name')->label('Unit')->visible(fn() => Auth::user()->hasRole(['super_admin', 'pengawas'])),
            
            Tables\Columns\TextColumn::make('items_count')->counts('items')->label('Jml Aktivitas')->badge()->color('info')->alignCenter(),
        ])
        ->filters([
            // --- BARIS 1 (3 Kolom @ 4 Grid) ---
            Tables\Filters\SelectFilter::make('directorate')
                ->label('Direktorat')
                ->placeholder('Pilih Direktorat')
                ->options(Directorate::pluck('name', 'id'))
                ->searchable()
                ->query(fn (Builder $query, array $data) => $query->when($data['value'], fn ($q, $v) => $q->whereHas('user.subunit.unit', fn ($subQ) => $subQ->where('directorate_id', $v))))
                ->visible(fn() => Auth::user()->hasRole(['super_admin', 'pengawas']))
                ->columnSpan(4), 

            Tables\Filters\SelectFilter::make('unit')
                ->label('Unit')
                ->placeholder('Pilih Unit')
                ->options(Unit::pluck('name', 'id'))
                ->searchable()
                ->query(fn (Builder $query, array $data) => $query->when($data['value'], fn ($q, $v) => $q->whereHas('user.subunit', fn ($subQ) => $subQ->where('unit_id', $v))))
                ->visible(fn() => Auth::user()->hasRole(['super_admin', 'pengawas']))
                ->columnSpan(4),

            Tables\Filters\SelectFilter::make('subunit')
                ->label('Sub Unit')
                ->placeholder('Pilih Sub Unit')
                ->options(Subunit::pluck('name', 'id'))
                ->searchable()
                ->query(fn (Builder $query, array $data) => $query->when($data['value'], fn ($q, $v) => $q->whereHas('user', fn ($subQ) => $subQ->where('subunit_id', $v))))
                ->visible(fn() => Auth::user()->hasRole(['super_admin', 'pengawas']))
                ->columnSpan(4), 

            // --- BARIS 2 (3 Kolom Input + 1 Kolom Tombol) ---
            Tables\Filters\SelectFilter::make('user_id')
                ->label('Cari Pegawai')
                ->placeholder('Pilih Pegawai')
                ->relationship('user', 'name')
                ->searchable()
                ->visible(fn() => Auth::user()->hasRole(['super_admin', 'pengawas']))
                ->columnSpan(3), // 3 dari 12

            Tables\Filters\SelectFilter::make('is_submitted')
                ->label('Status')
                ->placeholder('Pilih Status')
                ->options([0 => 'Draft', 1 => 'Final'])
                ->columnSpan(3), // 3 dari 12
            
            Tables\Filters\Filter::make('date_range')
                ->label('Rentang Tanggal') 
                ->form([
                    Grid::make(2)->schema([
                        DatePicker::make('created_from')->label('Dari'),
                        DatePicker::make('created_until')->label('Sampai'),
                    ]),
                ])
                ->query(fn (Builder $query, array $data) => $query
                    ->when($data['created_from'], fn ($q, $d) => $q->whereDate('date', '>=', $d))
                    ->when($data['created_until'], fn ($q, $d) => $q->whereDate('date', '<=', $d)))
                ->columnSpan(3), // 3 dari 12

            // --- TOMBOL CUSTOM (Kolom ke-4 di baris bawah) ---
            Tables\Filters\Filter::make('search_trigger')
                ->label('') // Label kosong agar sejajar
                ->form([
                    \Filament\Forms\Components\ViewField::make('search_btn')
                        ->view('filament.components.filter-button') // Memanggil View Custom
                        ->label('')
                ])
                ->columnSpan(3), // 3 dari 12

        ], layout: FiltersLayout::AboveContent)
        
        ->filtersFormColumns(12) // Menggunakan Grid 12
        ->deferFilters()
        
        // Sembunyikan tombol native (karena kita pakai tombol custom)
        ->filtersApplyAction(fn (\Filament\Tables\Actions\Action $action) => $action->hidden())
        
        ->actions([
            Tables\Actions\ViewAction::make(),
        ])
        ->bulkActions([]);
}
    
    public static function getRelations(): array { return []; }
    public static function getPages(): array {
        return [
            'index' => Pages\ListMonitoringLogbooks::route('/'),
        ];
    }
}