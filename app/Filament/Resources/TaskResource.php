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

class TaskResource extends Resource
{
    protected static ?string $model = Task::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'Daftar Tugas';
    protected static ?string $navigationGroup = 'Manajemen Tugas';

    public static function canCreate(): bool
    {
        return !Auth::user()->hasRole(['super_admin', 'pengawas']);
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
                                    1 => 'Rendah', 2 => 'Normal', 3 => 'Tinggi', 
                                    4 => 'Sangat Tinggi', 5 => 'Urgent'
                                ])
                                ->required()
                                ->default(2)
                                ->disabled($isAdminOrPengawas),

                            Forms\Components\DatePicker::make('deadline')
                                ->label('Tenggat Waktu')
                                ->required()
                                ->disabled($isAdminOrPengawas),
                            
                            Forms\Components\Select::make('status')
                                ->options([
                                    'pending' => 'Pending',
                                    'in_progress' => 'Sedang Dikerjakan',
                                    'completed' => 'Selesai'
                                ])
                                ->default('pending')
                                ->required()
                                ->disabled($isAdminOrPengawas),

                            Forms\Components\Select::make('user_id')
                                ->label('Ditugaskan Ke')
                                ->relationship('user', 'name')
                                ->searchable()
                                ->preload()
                                ->required()
                                ->visible(!$isAdminOrPengawas),

                            Forms\Components\TextInput::make('user.name')
                                ->label('Pegawai')
                                ->visible($isAdminOrPengawas)
                                ->disabled()
                                ->dehydrated(false),
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
            ->modifyQueryUsing(function (Builder $query, $livewire) {
                $query->with(['user.subunit.unit.directorate']);

                if (!Auth::user()->hasRole(['super_admin', 'pengawas'])) {
                    return $query->where('user_id', Auth::id());
                }

                $filters = $livewire->tableFilters;
                $hasSearchFilter = false;

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
            ->emptyStateDescription('Gunakan filter Direktorat, Unit, atau Pegawai untuk menampilkan data.')
            ->emptyStateIcon('heroicon-o-magnifying-glass')
            
            ->columns([
                // HAPUS ->searchable() AGAR BAR SEARCH HILANG
                Tables\Columns\TextColumn::make('title')->label('Judul')->limit(30),
                
                Tables\Columns\TextColumn::make('user.name')->label('Pegawai')->sortable()
                    ->visible(fn() => Auth::user()->hasRole(['super_admin', 'pengawas'])), 

                Tables\Columns\TextColumn::make('user.subunit.name')->label('Sub Unit')->sortable()
                    ->visible(fn() => Auth::user()->hasRole(['super_admin', 'pengawas'])),

                Tables\Columns\TextColumn::make('urgency')
                    ->label('Urgensi')
                    ->badge()
                    ->color(fn (int $state): string => match ($state) {
                        1 => 'gray', 2 => 'info', 3 => 'warning', 4, 5 => 'danger', default => 'gray',
                    }),
                 
                Tables\Columns\TextColumn::make('deadline')->date('d M Y')->sortable()
                    ->color(fn ($record) => $record->deadline < now() && $record->status !== 'completed' ? 'danger' : 'gray'),
                 
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->colors(['gray' => 'pending', 'warning' => 'in_progress', 'success' => 'completed']),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('directorate')
                    ->label('Direktorat')
                    ->placeholder('Pilih Direktorat') // <--- GANTI PLACEHOLDER
                    ->options(Directorate::pluck('name', 'id'))
                    ->query(fn (Builder $query, array $data) => $query->when($data['value'], fn ($q, $v) => $q->whereHas('user.subunit.unit', fn ($subQ) => $subQ->where('directorate_id', $v))))
                    ->visible(fn() => Auth::user()->hasRole(['super_admin', 'pengawas'])),

                Tables\Filters\SelectFilter::make('unit')
                    ->label('Unit')
                    ->placeholder('Pilih Unit') // <--- GANTI PLACEHOLDER
                    ->options(Unit::pluck('name', 'id'))
                    ->query(fn (Builder $query, array $data) => $query->when($data['value'], fn ($q, $v) => $q->whereHas('user.subunit', fn ($subQ) => $subQ->where('unit_id', $v))))
                    ->visible(fn() => Auth::user()->hasRole(['super_admin', 'pengawas'])),

                Tables\Filters\SelectFilter::make('subunit')
                    ->label('Sub Unit')
                    ->placeholder('Pilih Sub Unit') // <--- GANTI PLACEHOLDER
                    ->options(Subunit::pluck('name', 'id'))
                    ->query(fn (Builder $query, array $data) => $query->when($data['value'], fn ($q, $v) => $q->whereHas('user', fn ($subQ) => $subQ->where('subunit_id', $v))))
                    ->visible(fn() => Auth::user()->hasRole(['super_admin', 'pengawas'])),

                Tables\Filters\SelectFilter::make('user_id')
                    ->label('Pegawai')
                    ->placeholder('Pilih Pegawai') // <--- GANTI PLACEHOLDER
                    ->relationship('user', 'name')
                    ->searchable()
                    ->visible(fn() => Auth::user()->hasRole(['super_admin', 'pengawas'])),
                 
                Tables\Filters\SelectFilter::make('status')
                    ->placeholder('Pilih Status') // <--- GANTI PLACEHOLDER
                    ->options(['pending'=>'Pending', 'in_progress'=>'Proses', 'completed'=>'Selesai']),
                
                Tables\Filters\SelectFilter::make('urgency')
                    ->placeholder('Pilih Urgensi') // <--- GANTI PLACEHOLDER
                    ->options([1 => 'Rendah', 2 => 'Normal', 3 => 'Tinggi', 4 => 'Sangat Tinggi', 5 => 'Urgent']),

            ], layout: FiltersLayout::AboveContent)
            ->filtersFormColumns(3)
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Lihat Detail')
                    ->visible(fn () => Auth::user()->hasRole(['super_admin', 'pengawas'])),

                Tables\Actions\EditAction::make()
                    ->visible(fn ($record) => $record->user_id === Auth::id() && !Auth::user()->hasRole(['super_admin', 'pengawas'])),
                
                Tables\Actions\DeleteAction::make()
                    ->visible(fn ($record) => $record->user_id === Auth::id() && !Auth::user()->hasRole(['super_admin', 'pengawas'])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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