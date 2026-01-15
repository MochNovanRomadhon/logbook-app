<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DirectorateResource\Pages;
use App\Models\Directorate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class DirectorateResource extends Resource
{
    protected static ?string $model = Directorate::class;
    protected static ?string $navigationIcon = 'heroicon-o-building-library';
    protected static ?string $navigationGroup = 'Master Data';
    protected static ?string $navigationLabel = 'Direktorat';
    

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nama Direktorat')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Toggle::make('is_active')
                    ->label('Aktif')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\IconColumn::make('is_active')->boolean(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDirectorates::route('/'),
            'create' => Pages\CreateDirectorate::route('/create'),
            'edit' => Pages\EditDirectorate::route('/{record}/edit'),
        ];
    }
}