<?php

namespace App\Filament\Resources;

use BezhanSalleh\FilamentShield\Resources\RoleResource as ShieldRoleResource;
use Illuminate\Support\Facades\Auth; // <--- Jangan lupa import ini

class RoleResource extends ShieldRoleResource
{
    // 1. Masukkan ke grup yang sama dengan User
    protected static ?string $navigationGroup = 'Manajemen Pengguna';

    public static function getNavigationLabel(): string
    {
        return 'Role';
    }

    public static function getModelLabel(): string
    {
        return 'Role';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Role';
    }
    
    public static function getNavigationSort(): ?int
    {
        return 2; 
    }

    // 4. PERBAIKAN DI SINI
    public static function shouldRegisterNavigation(): bool
    {
        // Hanya tampilkan jika user yang login adalah 'super_admin'
        return Auth::user()?->hasRole('super_admin') ?? false;
    }

    // 5. Override fungsi grup agar tidak kembali ke default
    public static function getNavigationGroup(): ?string
    {
        return 'Manajemen Pengguna';
    }

    public static function form(\Filament\Forms\Form $form): \Filament\Forms\Form
    {
        return $form
            ->schema([
                \Filament\Forms\Components\Grid::make()
                    ->schema([
                        \Filament\Forms\Components\Section::make()
                            ->schema([
                                \Filament\Forms\Components\TextInput::make('name')
                                    ->label(__('filament-shield::filament-shield.field.name'))
                                    ->required()
                                    ->maxLength(255),

                                \Filament\Forms\Components\TextInput::make('guard_name')
                                    ->label(__('filament-shield::filament-shield.field.guard_name'))
                                    ->default(\BezhanSalleh\FilamentShield\Support\Utils::getFilamentAuthGuard())
                                    ->nullable()
                                    ->maxLength(255)
                                    ->hidden(),

                                \Filament\Forms\Components\Toggle::make('is_active')
                                    ->label('Status Aktif')
                                    ->default(true),

                                \BezhanSalleh\FilamentShield\Forms\ShieldSelectAllToggle::make('select_all')
                                    ->onIcon('heroicon-s-shield-check')
                                    ->offIcon('heroicon-s-shield-exclamation')
                                    ->label(__('filament-shield::filament-shield.field.select_all.name'))
                                    ->helperText(fn (): \Illuminate\Support\HtmlString => new \Illuminate\Support\HtmlString(__('filament-shield::filament-shield.field.select_all.message')))
                                    ->dehydrated(fn (bool $state): bool => $state),

                            ])
                            ->columns([
                                'sm' => 2,
                                'lg' => 3,
                            ]),
                    ]),
                static::getShieldFormComponents(),
            ]);
    }

    public static function table(\Filament\Tables\Table $table): \Filament\Tables\Table
    {
        return $table
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('name')
                    ->weight('font-medium')
                    ->label(__('filament-shield::filament-shield.column.name'))
                    ->formatStateUsing(fn ($state): string => \Illuminate\Support\Str::headline($state))
                    ->searchable(),
                \Filament\Tables\Columns\TextColumn::make('is_active')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state ? 'Aktif' : 'Tidak Aktif')
                    ->color(fn ($state) => $state ? 'success' : 'danger'),
                \Filament\Tables\Columns\TextColumn::make('permissions_count')
                    ->badge()
                    ->label(__('filament-shield::filament-shield.column.permissions'))
                    ->counts('permissions')
                    ->colors(['success']),
                \Filament\Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('filament-shield::filament-shield.column.updated_at'))
                    ->dateTime(),
            ])
            ->filters([
                \Filament\Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status Aktif')
                    ->boolean()
                    ->trueLabel('Aktif')
                    ->falseLabel('Tidak Aktif')
                    ->native(false),
            ])
            ->actions([
                \Filament\Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                \Filament\Tables\Actions\BulkActionGroup::make([
                    // no delete
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Resources\RoleResource\Pages\ListRoles::route('/'),
            'create' => \App\Filament\Resources\RoleResource\Pages\CreateRole::route('/create'),
            'view' => \BezhanSalleh\FilamentShield\Resources\RoleResource\Pages\ViewRole::route('/{record}'),
            'edit' => \App\Filament\Resources\RoleResource\Pages\EditRole::route('/{record}/edit'),
        ];
    }
}