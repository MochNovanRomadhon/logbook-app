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
                Forms\Components\Select::make('unit_id')
                    ->relationship('unit', 'name')
                    ->label('Induk Unit')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\TextInput::make('name')->label('Nama Sub Unit')->required(),
                Forms\Components\Toggle::make('is_active')->label('Status Aktif')->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('unit.name')->label('Unit')->sortable(),
                Tables\Columns\TextColumn::make('name')->label('Sub Unit')->searchable(),
                Tables\Columns\IconColumn::make('is_active')->boolean()->label('Status'),
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