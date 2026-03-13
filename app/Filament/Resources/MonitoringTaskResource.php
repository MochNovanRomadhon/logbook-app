<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MonitoringTaskResource\Pages;
use App\Models\Task;
use App\Models\LogbookItem;
use App\Models\Directorate;
use App\Models\Unit;
use App\Models\Subunit;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
                                    1 => '1',
                                    2 => '2',
                                    3 => '3',
                                    4 => '4',
                                    5 => '5',
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
                                    'pending'     => 'Menunggu',
                                    'in_progress' => 'Proses',
                                    'completed'   => 'Selesai',
                                    'cancelled'   => 'Batal',
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
                $currentUser = Auth::user();

                // Eager load relasi user untuk kolom subunit
                $query->with(['user.subunit', 'assigner']);

                // Hanya tampilkan task yang ditugaskan oleh pengawas ini (atau super_admin akses semua)
                if ($currentUser->hasRole('pengawas')) {
                    $query->where('assigned_by', $currentUser->id);
                }

                // — Grouping: Untuk task dengan task_group_id, hanya tampilkan 1 representatif —
                // Untuk setiap task_group_id, ambil hanya MIN(id)
                $minGroupIds = \Illuminate\Support\Facades\DB::table('tasks')
                    ->whereNotNull('task_group_id')
                    ->groupBy('task_group_id')
                    ->pluck(\Illuminate\Support\Facades\DB::raw('MIN(id)'))
                    ->toArray();

                $query->where(function ($q) use ($minGroupIds) {
                    $q->whereNull('task_group_id')
                      ->orWhereIn('id', $minGroupIds);
                });

                // Perlukan filter lokasi hanya untuk super_admin
                if ($currentUser->hasRole('super_admin')) {
                    $filters = $livewire->tableFilters;
                    $hasSearchFilter = !empty($filters['user_id']['value']) || 
                                       !empty($filters['location']['unit_id']) || 
                                       !empty($filters['location']['subunit_id']);

                    if (!$hasSearchFilter) {
                        return $query->whereRaw('1 = 0');
                    }
                }

                return $query;
            })
            ->defaultSort('deadline', 'asc')
            ->emptyStateHeading('Belum ada data ditampilkan')
            ->emptyStateDescription('Belum ada tugas yang Anda delegasikan.')
            ->emptyStateIcon('heroicon-o-magnifying-glass')
            
            ->columns([
                Tables\Columns\TextColumn::make('title')->label('Judul')->limit(30),
                
                Tables\Columns\TextColumn::make('assigned_to_names')
                    ->label('Ditugaskan Kepada')
                    ->getStateUsing(function (Task $record): string {
                        if ($record->task_group_id) {
                            $names = Task::where('task_group_id', $record->task_group_id)
                                ->with('user')
                                ->get()
                                ->pluck('user.name')
                                ->filter()
                                ->implode(', ');
                            return $names ?: '-';
                        }
                        return $record->user?->name ?? '-';
                    })
                    ->wrap(),

                Tables\Columns\TextColumn::make('user.subunit.name')->label('Sub Unit')->sortable()
                    ->visible(fn() => Auth::user()->hasRole(['super_admin', 'pengawas'])),

                Tables\Columns\TextColumn::make('urgency')
                    ->label('Urgensi')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => $state)
                    ->color(fn (string $state): string => match ($state) {
                        '1' => 'gray', '2' => 'info', '3' => 'warning',
                        '4' => 'danger', '5' => 'danger', default => 'gray',
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

                // Status (read-only)
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending'     => 'Menunggu',
                        'in_progress' => 'Proses',
                        'completed'   => 'Selesai',
                        'cancelled'   => 'Batal',
                        default       => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'pending'     => 'gray',
                        'in_progress' => 'info',
                        'completed'   => 'success',
                        'cancelled'   => 'danger',
                        default       => 'gray',
                    }),
            ])
            ->filters([
                // Filter Lokasi (hanya untuk super_admin, pengawas sudah dibatasi ke data sendiri)
                Tables\Filters\Filter::make('location')
                    ->form([
                        \Filament\Forms\Components\Grid::make(2)->schema([
                            \Filament\Forms\Components\Select::make('unit_id')
                                ->label('Unit')
                                ->placeholder('Pilih Unit')
                                ->options(function () {
                                    $user = Auth::user();
                                    if ($user->hasRole('super_admin')) {
                                        return Unit::where('is_active', true)->pluck('name', 'id');
                                    }
                                    // Pengawas: hanya unit miliknya
                                    return Unit::where('id', $user->unit_id)->where('is_active', true)->pluck('name', 'id');
                                })
                                ->live()
                                ->afterStateUpdated(fn (\Filament\Forms\Set $set) => $set('subunit_id', null)),
                            \Filament\Forms\Components\Select::make('subunit_id')
                                ->label('Sub Unit')
                                ->placeholder('Pilih Sub Unit')
                                ->options(fn (\Filament\Forms\Get $get) => Subunit::where('unit_id', $get('unit_id'))->where('is_active', true)->pluck('name', 'id')),
                        ])
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['unit_id'], fn ($q, $v) => $q->whereHas('user.subunit', fn ($subQ) => $subQ->where('unit_id', $v)))
                            ->when($data['subunit_id'], fn ($q, $v) => $q->whereHas('user', fn ($subQ) => $subQ->where('subunit_id', $v)));
                    })
                    ->visible(fn() => Auth::user()->hasRole(['super_admin', 'pengawas']))
                    ->columnSpan(12),

                Tables\Filters\SelectFilter::make('user_id')
                    ->label('Pegawai')
                    ->placeholder('Pilih Pegawai')
                    ->relationship('user', 'name', fn(Builder $query) => $query->where('is_active', true))
                    ->searchable()
                    ->visible(fn() => Auth::user()->hasRole('super_admin'))
                    ->columnSpan(3),

                Tables\Filters\SelectFilter::make('status')
                    ->placeholder('Pilih Status')
                    ->options(['pending' => 'Menunggu', 'in_progress' => 'Proses', 'completed' => 'Selesai', 'cancelled' => 'Batal'])
                    ->columnSpan(3),

                Tables\Filters\SelectFilter::make('urgency')
                    ->label('Urgensi')
                    ->placeholder('Pilih Urgensi')
                    ->options([1 => '1', 2 => '2', 3 => '3', 4 => '4', 5 => '5'])
                    ->columnSpan(3),

            ], layout: FiltersLayout::AboveContent)
            
            ->filtersFormColumns(12) 
            ->deferFilters()
            ->filtersApplyAction(fn (\Filament\Tables\Actions\Action $action) => $action->hidden())

            ->actions([
                Tables\Actions\Action::make('accept_task')
                    ->label('Terima')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Konfirmasi Penerimaan Tugas')
                    ->modalDescription('Anda yakin ingin menerima dan menyetujui tugas ini?')
                    ->modalSubmitActionLabel('Ya, Terima')
                    ->visible(function (\App\Models\Task $record): bool {
                        if ($record->accepted_at) return false;

                        // Jika ada task_group_id, semua task dalam grup harus selesai
                        if ($record->task_group_id) {
                            $allCompleted = Task::where('task_group_id', $record->task_group_id)
                                ->where('status', '!=', 'completed')
                                ->doesntExist();
                            return $allCompleted;
                        }

                        return $record->status === 'completed';
                    })
                    ->action(function (\App\Models\Task $record): void {
                        // Tandai semua task dalam grup sebagai diterima
                        if ($record->task_group_id) {
                            Task::where('task_group_id', $record->task_group_id)
                                ->update(['accepted_at' => now()]);
                        } else {
                            $record->update(['accepted_at' => now()]);
                        }
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
                                    'pending'     => 'Menunggu',
                                    'in_progress' => 'Proses',
                                    'completed'   => 'Selesai',
                                    'cancelled'   => 'Batal',
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
                        TextEntry::make('title')
                            ->label('Judul Tugas')
                            ->weight('bold')
                            ->size(TextEntrySize::Large)
                            ->columnSpanFull(),

                        TextEntry::make('description')
                            ->label('Deskripsi Tugas')
                            ->markdown()
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ]),
                
                InfoSection::make('Detail Tambahan')
                    ->schema([
                        InfoGrid::make(4)->schema([
                            TextEntry::make('urgency')
                                ->label('Tingkat Urgensi')
                                ->badge()
                                ->formatStateUsing(fn (string $state): string => $state)
                                ->color(fn (string $state): string => match ($state) {
                                    '1' => 'gray', '2' => 'info', '3' => 'warning',
                                    '4' => 'danger', '5' => 'danger', default => 'gray',
                                }),

                            TextEntry::make('deadline')
                                ->label('Tenggat Waktu')
                                ->date('d F Y'),
                                
                            TextEntry::make('assigner.name')
                                ->label('Ditugaskan Oleh')
                                ->badge()
                                ->color('gray')
                                ->getStateUsing(function (\App\Models\Task $record) {
                                    return $record->assigner?->name ?? '-';
                                }),

                            // "Ditugaskan Kepada" — nama semua pegawai yang terlibat
                            TextEntry::make('assigned_to_names')
                                ->label('Ditugaskan Kepada')
                                ->getStateUsing(function ($record): string {
                                    if ($record->task_group_id) {
                                        $names = Task::where('task_group_id', $record->task_group_id)
                                            ->with('user')
                                            ->get()
                                            ->pluck('user.name')
                                            ->filter()
                                            ->implode(', ');
                                        return $names ?: '-';
                                    }
                                    return $record->user?->name ?? '-';
                                })
                                ->badge()
                                ->color('primary'),
                                
                            TextEntry::make('user.subunit.name')
                                ->label('Pemilik Tugas (Sub Unit)')
                                ->visible(fn() => Auth::user()->hasRole(['super_admin', 'pengawas'])),
                        ]),
                    ]),

                InfoSection::make('Status & Riwayat Waktu')
                    ->schema([
                        InfoGrid::make(4)->schema([
                            TextEntry::make('status')
                                ->label('Status Saat Ini')
                                ->badge()
                                ->formatStateUsing(fn (string $state): string => match ($state) {
                                    'pending'     => 'Menunggu',
                                    'in_progress' => 'Proses',
                                    'completed'   => 'Selesai',
                                    'cancelled'   => 'Batal',
                                    default       => $state,
                                })
                                ->colors([
                                    'gray'    => 'pending',
                                    'info'    => 'in_progress',
                                    'success' => 'completed',
                                    'danger'  => 'cancelled',
                                ]),

                            TextEntry::make('created_at')
                                ->label('Dibuat Pada')
                                ->date('d F Y H:i')
                                ->placeholder('-'),
                                
                            TextEntry::make('processed_at')
                                ->label('Mulai Dikerjakan')
                                ->date('d F Y H:i')
                                ->placeholder('-'),
                            
                            TextEntry::make('completed_at')
                                ->label('Tanggal Selesai')
                                ->date('d F Y H:i')
                                ->placeholder('-'),

                            TextEntry::make('cancelled_at')
                                ->label('Tanggal Dibatalkan')
                                ->date('d F Y H:i')
                                ->placeholder('-'),

                            TextEntry::make('accepted_at')
                                ->label('Tanggal Diterima')
                                ->date('d F Y H:i')
                                ->placeholder('-'),
                        ]),
                    ])->collapsed(),

                InfoSection::make('Catatan')
                    ->schema([
                        \Filament\Infolists\Components\RepeatableEntry::make('all_logbook_items')
                            ->label('')
                            ->schema([
                                InfoGrid::make(4)->schema([
                                    TextEntry::make('logbook.date')
                                        ->label('Tanggal')
                                        ->date('d M Y'),
                                    TextEntry::make('pegawai_name')
                                        ->label('Pegawai')
                                        ->getStateUsing(fn ($record) => $record->logbook?->user?->name ?? '-'),
                                    TextEntry::make('activity')
                                        ->label('Deskripsi'),
                                    TextEntry::make('progress_info')
                                        ->label('Progress')
                                        ->getStateUsing(function ($record) {
                                            if (!$record->task_id) return 'N/A';
                                            $prev = $record->previous_progress ?? 0;
                                            $curr = $record->current_progress ?? 0;
                                            return "{$prev}% → {$curr}%";
                                        })
                                        ->badge()
                                        ->color(fn ($state) => str_contains($state, '100%') ? 'success' : ($state === 'N/A' ? 'gray' : 'primary')),
                                    TextEntry::make('task_status_label')
                                        ->label('Status Tugas')
                                        ->getStateUsing(fn ($record) => match($record->task?->status) {
                                            'pending'     => 'Menunggu',
                                            'in_progress' => 'Proses',
                                            'completed'   => 'Selesai',
                                            'cancelled'   => 'Batal',
                                            default       => '-',
                                        })
                                        ->badge()
                                        ->color(fn ($state) => match($state) {
                                            'Menunggu' => 'gray',
                                            'Proses'   => 'info',
                                            'Selesai'  => 'success',
                                            'Batal'    => 'danger',
                                            default    => 'gray',
                                        }),
                                ]),
                            ])
                            ->getStateUsing(function ($record) {
                                // Jika ada task_group_id, ambil semua logbook items dari semua task dalam grup
                                if ($record->task_group_id) {
                                    $groupTaskIds = Task::where('task_group_id', $record->task_group_id)->pluck('id');
                                    return LogbookItem::whereIn('task_id', $groupTaskIds)
                                        ->with(['logbook.user', 'task'])
                                        ->get()
                                        ->sortByDesc(fn ($item) => $item->logbook?->date)
                                        ->values();
                                }
                                // Task tunggal
                                return LogbookItem::where('task_id', $record->id)
                                    ->with(['logbook.user', 'task'])
                                    ->get()
                                    ->sortByDesc(fn ($item) => $item->logbook?->date)
                                    ->values();
                            })
                            ->placeholder('Belum ada catatan')
                            ->columns(1),
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMonitoringTasks::route('/'),
        ];
    }
}