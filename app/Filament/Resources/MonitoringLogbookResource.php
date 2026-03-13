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
    protected static ?string $slug = 'monitoring-logbook';
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationLabel = 'Monitoring Logbook';
    protected static ?string $navigationGroup = 'Monitoring';
    protected static ?int $navigationSort = 2;

    public static function getModelLabel(): string
    {
        return 'Monitoring Logbook';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Monitoring Logbook';
    }

    public static function shouldRegisterNavigation(): bool
    {
        return Auth::user()->can('view_any_monitoring::logbook')
            || Auth::user()->hasRole(['super_admin', 'pengawas']);
    }

    public static function canViewAny(): bool
    {
        return Auth::user()->can('view_any_monitoring::logbook')
            || Auth::user()->hasRole(['super_admin', 'pengawas']);
    }

    public static function canView($record): bool
    {
        return Auth::user()->can('view_monitoring::logbook')
            || Auth::user()->hasRole(['super_admin', 'pengawas']);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return Auth::user()->can('update_monitoring::logbook')
            || Auth::user()->hasRole(['super_admin', 'pengawas']);
    }

    public static function canDelete($record): bool
    {
        return Auth::user()->can('delete_monitoring::logbook')
            || Auth::user()->hasRole(['super_admin', 'pengawas']);
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
                                                ->options(function () {
                                                    $tasks = \App\Models\Task::where('user_id', Auth::id())
                                                        ->where('status', '!=', 'completed')
                                                        ->pluck('title', 'id')
                                                        ->toArray();
                                                    $tasks['other'] = '— Pekerjaan Lainnya —';
                                                    return $tasks;
                                                })
                                                ->searchable()
                                                ->preload()
                                                ->afterStateHydrated(function ($state, callable $set, $component) {
                                                    if (is_null($state) && $component->getRecord()) {
                                                        $component->state('other');
                                                    }
                                                })
                                                ->required()
                                                ->reactive()
                                                ->afterStateUpdated(function ($state, callable $set) {
                                                    if ($state && $state !== 'other') {
                                                        $lastLog = \App\Models\LogbookItem::where('task_id', $state)->latest()->first();
                                                        $set('previous_progress', $lastLog ? $lastLog->current_progress : 0);
                                                    } else {
                                                        $set('previous_progress', 0);
                                                        $set('current_progress', null);
                                                    }
                                                    if ($state !== 'other') {
                                                        $set('custom_task_name', null);
                                                    }
                                                })
                                                ->dehydrateStateUsing(fn ($state) => $state === 'other' ? null : $state)
                                                ->columnSpanFull(), 

                                            TextInput::make('custom_task_name')
                                                ->label('Nama Pekerjaan Lainnya')
                                                ->placeholder('Tuliskan nama pekerjaan...')
                                                ->required(fn (Get $get) => $get('task_id') === 'other')
                                                ->visible(fn (Get $get) => $get('task_id') === 'other')
                                                ->columnSpanFull(),

                                            TextInput::make('previous_progress')
                                                ->label('Progress Awal (%)')
                                                ->numeric()
                                                ->default(0)
                                                ->readOnly()
                                                ->suffix('%')
                                                ->visible(fn (Get $get) => $get('task_id') && $get('task_id') !== 'other'),

                                            TextInput::make('current_progress')
                                                ->label('Progress Akhir (%)')
                                                ->numeric()
                                                ->required(fn (Get $get) => $get('task_id') && $get('task_id') !== 'other')
                                                ->maxValue(100)
                                                ->suffix('%')
                                                ->visible(fn (Get $get) => $get('task_id') && $get('task_id') !== 'other'),

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
            $query->with(['user.subunit.unit.directorate'])->withCount('items');
            
            if (!Auth::user()->hasRole(['super_admin', 'pengawas'])) {
                return $query->where('user_id', Auth::id());
            }

            // Scope for Pengawas
            if (Auth::user()->hasRole('pengawas')) {
                $user = Auth::user();
                $query->whereHas('user', function ($q) use ($user) {
                    if ($user->subunit_id) {
                        $q->where('subunit_id', $user->subunit_id);
                    } elseif ($user->unit_id) {
                        $q->where('unit_id', $user->unit_id);
                    } elseif ($user->directorate_id) {
                        $q->where('directorate_id', $user->directorate_id);
                    }
                });
            }

            return $query;
        })
        ->defaultSort('date', 'desc')
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
                ->searchable()
                ->visible(fn() => Auth::user()->hasRole(['super_admin', 'pengawas'])),

            Tables\Columns\TextColumn::make('user.subunit.name')->label('Sub Unit')->sortable()->visible(fn() => Auth::user()->hasRole(['super_admin', 'pengawas'])),
            Tables\Columns\TextColumn::make('user.subunit.unit.name')->label('Unit')->visible(fn() => Auth::user()->hasRole(['super_admin', 'pengawas'])),
            
            Tables\Columns\TextColumn::make('items_count')->counts('items')->label('Jml Aktivitas')->badge()->color('info')->alignCenter(),


        ])
        ->filters([
            Tables\Filters\SelectFilter::make('is_submitted')
                ->label('Status')
                ->placeholder('Pilih Status')
                ->options([0 => 'Draft', 1 => 'Final']),
            
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
                    ->when($data['created_until'], fn ($q, $d) => $q->whereDate('date', '<=', $d))),

        ])
        
        ->actions([
            Tables\Actions\ViewAction::make(),
        ])
        ->bulkActions([]);
}

    // [4] Infolist Logbook untuk Monitoring
    public static function infolist(\Filament\Infolists\Infolist $infolist): \Filament\Infolists\Infolist
    {
        // InfoList yang sama dengan LogbookResource
        return $infolist
            ->schema([
                \Filament\Infolists\Components\Section::make('Informasi Utama')
                    ->schema([
                        \Filament\Infolists\Components\Grid::make(3)->schema([
                            \Filament\Infolists\Components\TextEntry::make('date')
                                ->label('Tanggal Laporan')
                                ->date('d F Y')
                                ->weight('bold'),

                            \Filament\Infolists\Components\TextEntry::make('is_submitted')
                                ->label('Status')
                                ->badge()
                                ->formatStateUsing(fn (bool $state) => $state ? 'Final (Terkunci)' : 'Draft')
                                ->colors(['gray' => false, 'success' => true]),

                            \Filament\Infolists\Components\TextEntry::make('user.name')
                                ->label('Pegawai')
                                ->weight('bold')
                                ->visible(fn() => Auth::user()->hasRole(['super_admin', 'pengawas'])),
                        ]),
                    ]),

                \Filament\Infolists\Components\Section::make('Rincian Aktivitas')
                    ->schema([
                        \Filament\Infolists\Components\RepeatableEntry::make('items')
                            ->label('')
                            ->schema([
                                \Filament\Infolists\Components\Grid::make(4)->schema([
                                    \Filament\Infolists\Components\TextEntry::make('task.title')
                                        ->label('Pekerjaan/Tugas')
                                        ->getStateUsing(fn ($record) => $record->task_id ? $record->task->title : $record->custom_task_name)
                                        ->weight('bold')
                                        ->size(\Filament\Infolists\Components\TextEntry\TextEntrySize::Large)
                                        ->columnSpan(2),

                                    \Filament\Infolists\Components\TextEntry::make('progress_change')
                                        ->label('Progress')
                                        ->getStateUsing(function ($record) {
                                            if (!$record->task_id) return 'N/A';
                                            $prev = $record->previous_progress ?? 0;
                                            $curr = $record->current_progress ?? 0;
                                            return "{$prev}% ➝ {$curr}%";
                                        })
                                        ->badge()
                                        ->color(fn ($state) => str_contains($state, '100%') ? 'success' : ($state === 'N/A' ? 'gray' : 'primary'))
                                        ->columnSpan(2),

                                    \Filament\Infolists\Components\TextEntry::make('activity')
                                        ->label('Deskripsi Aktivitas')
                                        ->markdown()
                                        ->columnSpanFull(),
                                ]),
                            ])
                            ->columns(1)
                    ])
            ]);
    }
    
    public static function getRelations(): array { return []; }
    public static function getPages(): array {
        return [
            'index' => Pages\ListMonitoringLogbooks::route('/'),
        ];
    }
}