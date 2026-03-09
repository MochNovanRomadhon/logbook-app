<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SubunitResource\Pages;
use App\Models\Subunit;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Illuminate\Database\Eloquent\Builder;

class SubunitResource extends Resource
{
    protected static ?string $model = Subunit::class;
    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';
    protected static ?string $navigationGroup = 'Master Data';
    protected static ?int $navigationSort = 3;

    public static function shouldRegisterNavigation(): bool
    {
        return Auth::user()->hasRole('super_admin');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('directorate_id')
                    ->label('Induk Direktorat')
                    ->options(\App\Models\Directorate::where('is_active', true)->pluck('name', 'id'))
                    ->live()
                    ->searchable()
                    ->preload()
                    ->afterStateHydrated(function (Forms\Components\Select $component, $state, ?\Illuminate\Database\Eloquent\Model $record) {
                        if ($record && $record->unit_id) {
                            $unit = \App\Models\Unit::find($record->unit_id);
                            if ($unit) {
                                $component->state($unit->directorate_id);
                            }
                        }
                    })
                    ->afterStateUpdated(fn (Set $set) => $set('unit_id', null))
                    ->dehydrated(false)
                    ->required()
                    ->validationMessages(['required' => 'Wajib diisi.']),
                Forms\Components\Select::make('unit_id')
                    ->label('Induk Unit')
                    ->options(fn (Get $get) => \App\Models\Unit::where('directorate_id', $get('directorate_id'))->where('is_active', true)->pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->required()
                    ->validationMessages(['required' => 'Wajib diisi.']),
                Forms\Components\TextInput::make('name')
                    ->label('Nama Sub Unit')
                    ->required()
                    ->validationMessages(['required' => 'Wajib diisi.']),
                Forms\Components\Toggle::make('is_active')->label('Status Aktif')->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['unit.directorate']))
            ->columns([
                Tables\Columns\TextColumn::make('unit.directorate.name')->label('Direktorat')->sortable(),
                Tables\Columns\TextColumn::make('unit.name')->label('Unit')->sortable(),
                Tables\Columns\TextColumn::make('name')->label('Sub Unit')->searchable(),
                Tables\Columns\TextColumn::make('is_active')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state ? 'Aktif' : 'Tidak Aktif')
                    ->color(fn ($state) => $state ? 'success' : 'danger'),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status Aktif')
                    ->boolean()
                    ->trueLabel('Aktif')
                    ->falseLabel('Tidak Aktif')
                    ->native(false),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                // Tables\Actions\DeleteAction::make(),
            ]);
    }
    
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSubunits::route('/'),
            'create' => Pages\CreateSubunit::route('/create'),
            'edit' => Pages\EditSubunit::route('/{record}/edit'),
        ];
    }
}