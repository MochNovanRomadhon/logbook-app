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
            ->modifyQueryUsing(function (Builder $query) {
                return $query->where('user_id', Auth::id());
            })
            ->emptyStateHeading('Belum ada logbook')
            ->emptyStateDescription('Buat logbook harian Anda sekarang.')
            ->emptyStateIcon('heroicon-o-book-open')

            ->columns([
                Tables\Columns\TextColumn::make('date')->date('d F Y')->label('Tanggal')->sortable(),
                
                Tables\Columns\TextColumn::make('is_submitted')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (bool $state) => $state ? 'Final' : 'Draft')
                    ->colors(['gray' => false, 'success' => true])
                    ->icon(fn (bool $state) => $state ? 'heroicon-o-lock-closed' : 'heroicon-o-pencil'),

                // HAPUS ->searchable() AGAR BAR SEARCH HILANG
                Tables\Columns\TextColumn::make('items_count')->counts('items')->label('Jml Aktivitas')->badge()->color('info')->alignCenter(),
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
            ], layout: FiltersLayout::AboveContent)
            ->filtersFormColumns(2) 
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
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