<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TaskResource\Pages;
use App\Models\Task;
use App\Models\User;
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

class TaskResource extends Resource
{
    protected static ?string $model = Task::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Manajemen Tugas';
    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Tugas';
    protected static ?string $pluralModelLabel = 'Daftar Tugas';
    protected static ?string $navigationLabel = 'Daftar Tugas';

    public static function shouldRegisterNavigation(): bool
    {
        return !Auth::user()->hasRole('super_admin');
    }

    public static function canCreate(): bool
    {
        return true; 
    }

    public static function form(Form $form): Form
    {
        $isDisabled = false;

        return $form
            ->schema([
                Section::make()
                    ->schema([
                        Forms\Components\Select::make('user_ids')
                            ->label('Ditugaskan ke')
                            ->options(function () {
                                $currentUser = Auth::user();
                                return User::query()
                                    ->where(function ($q) use ($currentUser) {
                                        $q->whereHas('roles', fn($r) => $r->where('name', 'pegawai'))
                                          ->orWhere('id', $currentUser->id);
                                    })
                                    ->where('is_active', true)
                                    ->pluck('name', 'id');
                            })
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->required()
                            ->visible(fn () => Auth::user()->hasRole('pengawas'))
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('title')
                            ->label('Judul Tugas')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull()
                            ->disabled($isDisabled),
                        
                        Grid::make(2)->schema([
                            Forms\Components\Select::make('urgency')
                                ->label('Tingkat Urgensi')
                                ->options([
                                    1 => '1', 2 => '2', 3 => '3', 
                                    5 => '4'
                                ])
                                ->required()
                                ->default(2)
                                ->disabled($isDisabled),

                            Forms\Components\DatePicker::make('deadline')
                                ->label('Tenggat Waktu')
                                ->required()
                                ->disabled($isDisabled),
                            
                            Forms\Components\Select::make('status')
                                ->label('Status')
                                ->options([
                                    'pending' => 'Belum',
                                    'in_progress' => 'Sedang Dikerjakan',
                                    'completed' => 'Selesai',
                                    'cancelled' => 'Batal'
                                ])
                                ->default('pending')
                                ->required()
                                ->disabled($isDisabled),
                        ]),

                        Forms\Components\Textarea::make('description')
                            ->label('Deskripsi')
                            ->rows(3)
                            ->columnSpanFull()
                            ->disabled($isDisabled),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordUrl(fn (Task $record): string => Pages\EditTask::getUrl(['record' => $record->getKey()])) 
            
            // [1] Sortir default + eager load assigner
            ->defaultSort('deadline', 'asc')
            ->modifyQueryUsing(function (Builder $query) {
                return $query->with('assigner')->where('user_id', Auth::id());
            })
            ->emptyStateHeading('Belum ada tugas')
            ->emptyStateDescription('Buat tugas baru untuk memulai.')
            ->emptyStateIcon('heroicon-o-clipboard-document-list')
            
            ->columns([
                Tables\Columns\TextColumn::make('title')->label('Judul')->limit(30)->searchable(),
                
                Tables\Columns\TextColumn::make('urgency')
                    ->label('Urgensi')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => match($state) {
                        '5' => '4',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        '1' => 'gray',
                        '2' => 'info',
                        '3' => 'warning',
                        '4' => 'danger',
                        '5' => 'danger',
                        default => 'gray',
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
                    })
                    ->label('Tenggat Waktu'),

                // [3] Kolom "Ditugaskan Oleh"
                Tables\Columns\TextColumn::make('assigner.name')
                    ->label('Ditugaskan Oleh')
                    ->placeholder('Inisiatif Sendiri')
                    ->toggleable(isToggledHiddenByDefault: true),
                 
                // Status (read-only)
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Menunggu',
                        'in_progress' => 'Sedang Proses',
                        'completed' => 'Selesai',
                        'cancelled' => 'Batal',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'gray',
                        'in_progress' => 'info',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->placeholder('Pilih Status')
                    ->options(['pending'=>'Menunggu', 'in_progress'=>'Proses', 'completed'=>'Selesai', 'cancelled'=>'Batal']),
                
                Tables\Filters\SelectFilter::make('urgency')
                    ->label('Urgensi')
                    ->placeholder('Pilih Urgensi')
                    ->options([1 => '1', 2 => '2', 3 => '3', 5 => '4']),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Lihat Detail')
                    ->modalHeading('Rincian Tugas'),

                Tables\Actions\EditAction::make()
                    ->label('Ubah'),
                ]);
            // ->bulkActions([
            //     Tables\Actions\BulkActionGroup::make([
            //         Tables\Actions\BulkAction::make('update_status')
            //             ->label('Perbarui Status')
            //             ->icon('heroicon-o-pencil-square')
            //             ->color('primary')
            //             ->form([
            //                 \Filament\Forms\Components\Select::make('status')
            //                     ->label('Pilih Status Baru')
            //                     ->options([
            //                         'pending' => 'Menunggu',
            //                         'in_progress' => 'Sedang Proses',
            //                         'completed' => 'Selesai',
            //                         'cancelled' => 'Batal',
            //                     ])
            //                     ->required(),
            //             ])
            //             ->action(function (\Illuminate\Database\Eloquent\Collection $records, array $data): void {
            //                 foreach ($records as $record) {
            //                     $updateData = ['status' => $data['status']];
            //                     if ($data['status'] === 'completed') {
            //                         $updateData['completed_at'] = now();
            //                     } elseif ($data['status'] === 'cancelled') {
            //                         $updateData['cancelled_at'] = now();
            //                     }
            //                     $record->update($updateData);
            //                 }
            //             })
            //             ->deselectRecordsAfterCompletion(),
                        
            //         // Tables\Actions\DeleteBulkAction::make(),
            //     ]),
            // ]);
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
                        ]),
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
                                ->label('Tanggal Selesai')
                                ->date('d F Y H:i')
                                ->placeholder('-'),

                            TextEntry::make('cancelled_at')
                                ->label('Tanggal Dibatalkan')
                                ->date('d F Y H:i')
                                ->placeholder('-'),
                        ]),
                    ])->collapsed(),

                InfoSection::make('Catatan')
                    ->schema([
                        \Filament\Infolists\Components\RepeatableEntry::make('logbookItems')
                            ->label('')
                            ->schema([
                                InfoGrid::make(4)->schema([
                                    TextEntry::make('logbook.date')
                                        ->label('Tanggal')
                                        ->date('d M Y'),
                                    TextEntry::make('activity')
                                        ->label('Deskripsi'),
                                    TextEntry::make('progress_change')
                                        ->label('Progress')
                                        ->getStateUsing(fn ($record) => ($record->previous_progress ?? 0) . '% → ' . ($record->current_progress ?? 0) . '%')
                                        ->badge()
                                        ->color(fn ($state) => str_contains($state, '100%') ? 'success' : 'primary'),
                                    TextEntry::make('logbook.is_submitted')
                                        ->label('Status')
                                        ->formatStateUsing(fn ($state) => $state ? 'Final' : 'Draft')
                                        ->badge()
                                        ->color(fn ($state) => $state ? 'success' : 'gray'),
                                ]),
                            ])
                            ->placeholder('Belum ada catatan')
                            ->columns(1),
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTasks::route('/'),
            'create' => Pages\CreateTask::route('/create'),
            'edit' => Pages\EditTask::route('/{record}/edit'),
        ];
    }
}