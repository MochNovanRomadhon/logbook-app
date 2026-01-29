<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TaskResource\Pages;
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
        // Allow All (Pegawai & Pengawas, except Super Admin maybe? Or just all)
        return true; 
    }

    public static function form(Form $form): Form
    {
        // Enable fields for everyone (since it's personal task)
        $isDisabled = false; // Auth::user()->hasRole(['super_admin']); // Optional: Disable for Super Admin if needed

        return $form
            ->schema([
                Section::make()
                    ->schema([
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
                                    1 => 'Rendah', 2 => 'Normal', 3 => 'Tinggi', 
                                    4 => 'Sangat Tinggi', 5 => 'Urgent'
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
                                    'pending' => 'Pending',
                                    'in_progress' => 'Sedang Dikerjakan',
                                    'completed' => 'Selesai'
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
            ->recordAction(Tables\Actions\ViewAction::class) 
            ->recordUrl(null) 
            
            // STRICT PERSONAL QUERY
            ->modifyQueryUsing(function (Builder $query) {
                return $query->where('user_id', Auth::id());
            })
            ->emptyStateHeading('Belum ada tugas')
            ->emptyStateDescription('Buat tugas baru untuk memulai.')
            ->emptyStateIcon('heroicon-o-clipboard-document-list')
            
            ->columns([
                Tables\Columns\TextColumn::make('title')->label('Judul')->limit(30),
                
                Tables\Columns\TextColumn::make('urgency')
                    ->label('Urgensi')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        '1' => 'Rendah', '2' => 'Normal', '3' => 'Tinggi', 
                        '4' => 'Sangat Tinggi', '5' => 'Urgent', default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        '1' => 'gray', '2' => 'info', '3' => 'warning', '4', '5' => 'danger', default => 'gray',
                    }),
                 
                Tables\Columns\TextColumn::make('deadline')->date('d M Y')->sortable()
                    ->color(fn ($record) => $record->deadline < now() && $record->status !== 'completed' ? 'danger' : 'gray'),
                 
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Menunggu',
                        'in_progress' => 'Sedang Proses',
                        'completed' => 'Selesai',
                        default => $state,
                    })
                    ->colors(['gray' => 'pending', 'warning' => 'in_progress', 'success' => 'completed']),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->placeholder('Pilih Status')
                    ->options(['pending'=>'Pending', 'in_progress'=>'Proses', 'completed'=>'Selesai']),
                
                Tables\Filters\SelectFilter::make('urgency')
                    ->placeholder('Pilih Urgensi')
                    ->options([1 => 'Rendah', 2 => 'Normal', 3 => 'Tinggi', 4 => 'Sangat Tinggi', 5 => 'Urgent']),
            ], layout: FiltersLayout::AboveContent)
            ->filtersFormColumns(3)
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Lihat Detail')
                    ->modalHeading('Rincian Tugas'),

                Tables\Actions\EditAction::make()
                    ->label('Ubah'),
                
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                InfoSection::make('Detail Informasi Tugas')
                    ->schema([
                        TextEntry::make('title')
                            ->label('Judul Tugas')
                            ->weight('bold')
                            ->size(TextEntrySize::Large)
                            ->columnSpanFull(),

                        InfoGrid::make(2)->schema([
                            TextEntry::make('urgency')
                                ->label('Tingkat Urgensi')
                                ->badge()
                                ->formatStateUsing(fn (string $state): string => match ($state) {
                                    '1' => 'Rendah', '2' => 'Normal', '3' => 'Tinggi',
                                    '4' => 'Sangat Tinggi', '5' => 'Urgent', default => $state,
                                })
                                ->color(fn (string $state): string => match ($state) {
                                    '1' => 'gray', '2' => 'info', '3' => 'warning', '4', '5' => 'danger', default => 'gray',
                                }),

                            TextEntry::make('deadline')
                                ->label('Tenggat Waktu')
                                ->date('d M Y'),

                            TextEntry::make('status')
                                ->label('Status')
                                ->badge()
                                ->formatStateUsing(fn (string $state): string => match ($state) {
                                    'pending' => 'Menunggu',
                                    'in_progress' => 'Sedang Proses',
                                    'completed' => 'Selesai',
                                    default => $state,
                                })
                                ->colors([
                                    'gray' => 'pending', 'warning' => 'in_progress', 'success' => 'completed',
                                ]),
                            
                            // Tetap sembunyikan Sub Unit jika Pegawai biasa (sesuai request awal)
                            // Jika ingin ditampilkan untuk pegawai juga, hapus baris visible() ini
                            TextEntry::make('user.subunit.name')
                                ->label('Sub Unit')
                                ->visible(fn() => Auth::user()->hasRole(['super_admin', 'pengawas'])),
                        ]),

                        TextEntry::make('description')
                            ->label('Deskripsi')
                            ->markdown()
                            ->placeholder('Tidak ada deskripsi')
                            ->columnSpanFull(),
                    ])
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