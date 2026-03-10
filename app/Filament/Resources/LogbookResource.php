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
                                        ->maxDate(now())
                                        ->unique(ignoreRecord: true, modifyRuleUsing: fn (\Illuminate\Validation\Rules\Unique $rule) => $rule->where('user_id', Auth::id()))
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
                                                ->options(function (Get $get, $component) {
                                                    $tasks = \App\Models\Task::where('user_id', Auth::id())
                                                        ->whereNotIn('status', ['completed', 'cancelled'])
                                                        ->pluck('title', 'id')
                                                        ->toArray();

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

                // [6] Kolom progress rata-rata
                Tables\Columns\TextColumn::make('average_progress')
                    ->label('Rata-rata Progress')
                    ->getStateUsing(function (Logbook $record) {
                        $items = $record->items;
                        // Hanya hitung item yang memiliki tugas (bukan custom task) dan memiliki nilai progress
                        $taskItems = $items->filter(fn($item) => $item->task_id !== null && $item->current_progress !== null);
                        
                        if ($taskItems->isEmpty()) return '-';
                        
                        $avg = $taskItems->avg('current_progress');
                        return number_format($avg, 0) . '%';
                    })
                    ->badge()
                    ->color(fn(string $state) => $state === '100%' ? 'success' : ($state === '-' ? 'gray' : 'primary')),

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

                        // [7] Logika update status tugas 100%
                        foreach ($record->items as $item) {
                            if ($item->task_id && $item->current_progress == 100) {
                                \App\Models\Task::where('id', $item->task_id)->update([
                                    'status' => 'completed',
                                    'completed_at' => now(),
                                ]);
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

                            \Filament\Infolists\Components\TextEntry::make('items_count')
                                ->label('Total Aktivitas')
                                ->getStateUsing(fn($record) => $record->items()->count()),
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
            'index' => Pages\ListLogbooks::route('/'),
            'create' => Pages\CreateLogbook::route('/create'),
            'edit' => Pages\EditLogbook::route('/{record}/edit'),
        ];
    }
}