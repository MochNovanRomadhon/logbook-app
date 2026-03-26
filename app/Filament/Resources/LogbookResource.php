<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LogbookResource\Pages;
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
use Filament\Forms\Get;
use Filament\Tables\Enums\FiltersLayout;

class LogbookResource extends Resource
{
    protected static ?string $model = Logbook::class;
    protected static ?string $navigationIcon = 'heroicon-o-book-open';
    protected static ?string $navigationLabel = 'Logbook Harian';
    protected static ?string $navigationGroup = 'Manajemen Tugas';
    protected static ?int $navigationSort = 2;

    public static function shouldRegisterNavigation(): bool
    {
        return Auth::user()->can('view_any_logbook')
            && !Auth::user()->hasRole('super_admin');
    }

    public static function canViewAny(): bool
    {
        return Auth::user()->can('view_any_logbook');
    }

    public static function canCreate(): bool
    {
        return Auth::user()->can('create_logbook');
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
                                        ->disabled()
                                        ->dehydrated()
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
                                                ->helperText('Tugas yang tidak tampil berarti belum berstatus "Proses". Ubah status terlebih dahulu di Daftar Tugas. Jika memilih "Pekerjaan Lainnya" dan tidak selesai dalam sekali pengerjaan, wajib dibuatkan sebagai tugas baru.')
                                                ->options(function (Get $get, $component) {
                                                    $taskQuery = \App\Models\Task::where('user_id', Auth::id())
                                                        ->where('status', 'in_progress')
                                                        ->orderBy('deadline', 'asc')
                                                        ->get();

                                                    $tasks = [];
                                                    $now = now()->startOfDay();
                                                    
                                                    foreach ($taskQuery as $task) {
                                                        $deadline = \Carbon\Carbon::parse($task->deadline)->startOfDay();
                                                        
                                                        if ($deadline->isBefore($now)) {
                                                            $deadlineText = 'Terlambat';
                                                        } else {
                                                            $diffDays = $now->diffInDays($deadline); 
                                                            if ($diffDays == 0) {
                                                                $deadlineText = 'Hari ini';
                                                            } else {
                                                                $deadlineText = "H-{$diffDays}";
                                                            }
                                                        }
                                                        
                                                        $tasks[$task->id] = "{$task->title} ({$deadlineText})";
                                                    }

                                                    // Ambil semua task_id yang sudah dipilih di repeater lain
                                                    $allItems = $get('../../items') ?? [];
                                                    $currentPath = $component->getStatePath();
                                                    $selectedTaskIds = [];

                                                    foreach ($allItems as $uuid => $item) {
                                                        $itemTaskId = $item['task_id'] ?? null;
                                                        // Jangan exclude diri sendiri & jangan exclude 'other'
                                                        if ($itemTaskId && $itemTaskId !== 'other' && !str_contains($currentPath, $uuid)) {
                                                            $selectedTaskIds[] = $itemTaskId;
                                                        }
                                                    }

                                                    // Hapus tugas yang sudah dipilih dari opsi
                                                    foreach ($selectedTaskIds as $id) {
                                                        unset($tasks[$id]);
                                                    }

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
                                                ->afterStateUpdated(function ($state, callable $set, Get $get) {
                                                    if ($state && $state !== 'other') {
                                                        $date = $get('../../date') ?? now()->format('Y-m-d');
                                                        $lastLog = \App\Models\LogbookItem::where('task_id', $state)
                                                            ->whereHas('logbook', function ($query) use ($date) {
                                                                $query->where('date', '<', $date);
                                                            })
                                                            ->join('logbooks', 'logbooks.id', '=', 'logbook_items.logbook_id')
                                                            ->orderByDesc('logbooks.date')
                                                            ->orderByDesc('logbook_items.id')
                                                            ->select('logbook_items.*')
                                                            ->first();
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
                                                ->helperText('Opsional: Jika "Pekerjaan Lainnya" tidak selesai sekaligus 100%, harap buat sebagai tugas baru di Daftar Tugas untuk kemudahan tracking/monitoring.')
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
            ->modifyQueryUsing(function (Builder $query) {
                return $query->with('items')->withCount('items')->where('user_id', Auth::id());
            })
            // [1] Sortir default berdasarkan tanggal terbaru
            ->defaultSort('date', 'desc')
            ->emptyStateHeading('Belum ada logbook')
            ->emptyStateDescription('Buat logbook harian Anda sekarang.')
            ->emptyStateIcon('heroicon-o-book-open')

            ->columns([
                Tables\Columns\TextColumn::make('date')->date('d F Y')->label('Tanggal')->sortable(),
                
                Tables\Columns\TextColumn::make('items_count')->counts('items')->label('Jml Aktivitas')->badge()->color('info')->alignCenter(),


                Tables\Columns\TextColumn::make('is_submitted')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (bool $state) => $state ? 'Final' : 'Draft')
                    ->colors(['gray' => false, 'success' => true])
                    ->icon(fn (bool $state) => $state ? 'heroicon-o-lock-closed' : 'heroicon-o-pencil'),
            ])
            ->filters([
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
                
                Tables\Filters\SelectFilter::make('is_submitted')
                    ->label('Status')
                    ->placeholder('Pilih Status') // <--- GANTI PLACEHOLDER
                    ->options([0 => 'Draft', 1 => 'Final']),
            ])
            ->filtersFormColumns(2) 
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn ($record) => !$record->is_submitted),

                // [2] Tombol "Finalkan" cepat
                Tables\Actions\Action::make('finalize')
                    ->label('Finalkan')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Finalkan Logbook')
                    ->modalDescription('Apakah Anda yakin ingin memfinalkan logbook ini? Setelah difinalkan, logbook tidak dapat diubah lagi.')
                    ->modalSubmitActionLabel('Ya, Finalkan')
                    ->visible(fn ($record) => !$record->is_submitted)
                    ->action(function (Logbook $record) {
                        $record->update(['is_submitted' => true]);

                        // [7] Logika update status tugas 100% atau Menunggu -> Proses
                        foreach ($record->items as $item) {
                            if ($item->task_id) {
                                $task = \App\Models\Task::find($item->task_id);
                                if ($task) {
                                    if ($item->current_progress == 100) {
                                        $task->update([
                                            'status' => 'completed',
                                            'completed_at' => now(),
                                        ]);
                                    } elseif ($task->status === 'pending') {
                                        $task->update([
                                            'status' => 'in_progress',
                                            'processed_at' => now(),
                                        ]);
                                    }
                                }
                            }
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    // [4] Infolist Logbook
    public static function infolist(\Filament\Infolists\Infolist $infolist): \Filament\Infolists\Infolist
    {
        return $infolist
            ->schema([
                \Filament\Infolists\Components\Section::make('')
                    ->schema([
                        \Filament\Infolists\Components\Grid::make(3)->schema([
                            \Filament\Infolists\Components\TextEntry::make('date')
                                ->label('Tanggal')
                                ->date('d F Y')
                                ->weight('bold'),

                            \Filament\Infolists\Components\TextEntry::make('user.name')
                                ->label('Dibuat oleh')
                                ->weight('bold'),

                            \Filament\Infolists\Components\TextEntry::make('unit_subunit_info')
                                ->label('Unit - Sub Unit')
                                ->getStateUsing(function ($record) {
                                    $unit = $record->user?->subunit?->unit?->name ?? $record->user?->unit?->name ?? '-';
                                    $subunit = $record->user?->subunit?->name ?? '-';
                                    return "{$unit} - {$subunit}";
                                }),
                        ]),
                    ]),

                \Filament\Infolists\Components\Section::make('')
                    ->schema([
                        \Filament\Infolists\Components\RepeatableEntry::make('items')
                            ->label('')
                            ->schema([
                                \Filament\Infolists\Components\Grid::make(5)->schema([
                                    \Filament\Infolists\Components\TextEntry::make('activity')
                                        ->label('Deskripsi Pekerjaan')
                                        ->columnSpan(1),

                                    \Filament\Infolists\Components\TextEntry::make('task_name')
                                        ->label('Tugas')
                                        ->getStateUsing(fn ($record) => $record->task_id ? ($record->task?->title ?? '-') : ($record->custom_task_name ?? '-'))
                                        ->columnSpan(1),

                                    \Filament\Infolists\Components\TextEntry::make('previous_progress')
                                        ->label('Persentase Sebelumnya')
                                        ->getStateUsing(fn ($record) => $record->task_id ? (($record->previous_progress ?? 0) . '%') : '-')
                                        ->alignCenter()
                                        ->columnSpan(1),

                                    \Filament\Infolists\Components\TextEntry::make('current_progress')
                                        ->label('Persentase Sekarang')
                                        ->getStateUsing(fn ($record) => $record->task_id ? (($record->current_progress ?? 0) . '%') : '-')
                                        ->alignCenter()
                                        ->columnSpan(1),

                                    \Filament\Infolists\Components\TextEntry::make('task_status_label')
                                        ->label('Status')
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
                                        })
                                        ->columnSpan(1),
                                ]),
                            ])
                            ->columns(1)
                    ])
            ]);
    }
    
    public static function getRelations(): array { return []; }
    public static function getPages(): array {
        return [
            'index' => Pages\ListLogbooks::route('/'),
            'create' => Pages\CreateLogbook::route('/create'),
            'edit' => Pages\EditLogbook::route('/{record}/edit'),
        ];
    }
}