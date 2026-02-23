<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use App\Models\Unit;      // Tambahan
use App\Models\Subunit;   // Tambahan
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;   // Penting untuk logika bertingkat
use Filament\Forms\Set;   // Penting untuk reset input
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Spatie\Permission\Models\Role;
use Illuminate\Validation\Rules\Password; // <--- Tambahkan ini di atas
use Illuminate\Database\Eloquent\Builder;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users'; // Ikon diganti biar lebih cocok
    protected static ?string $navigationGroup = 'Manajemen Pengguna'; // Opsional:
    protected static ?string $navigationLabel = 'Kelola Pengguna';
    protected static ?int $navigationSort = 1;

   public static function form(Form $form): Form
{
    return $form
        ->schema([
            Forms\Components\Section::make('Informasi Akun')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Nama Lengkap')
                        ->required()
                        ->maxLength(255),
                    
                    // --- UPDATE 1: VALIDASI EMAIL ---
                    Forms\Components\TextInput::make('email')
                        ->email() // Wajib format email (ada @ dan .)
                        ->unique(ignoreRecord: true) // Tidak boleh ada email ganda di database
                        ->required()
                        ->maxLength(255),

                    // Role (Single Select)
                    Forms\Components\Select::make('roles')
                        ->relationship('roles', 'name')
                        ->label('Role')
                        ->preload()
                        ->searchable()
                        ->required()
                        ->live()
                        ->afterStateUpdated(fn (Set $set) => $set('directorate_id', null)),

                    // --- UPDATE 2: PASSWORD CANGGIH ---
                    Forms\Components\TextInput::make('password')
                        ->password()
                        ->revealable()
                        ->rule(Password::min(8)
                            ->mixedCase()
                            ->numbers()
                        )
                        ->validationAttribute('password')
                        ->dehydrated(fn ($state) => filled($state))
                        ->required(fn (string $context): bool => $context === 'create')
                        ->helperText('Minimal 8 karakter, harus mengandung huruf besar & angka. Kosongkan jika tidak ingin mengubah.'),

                    Forms\Components\Toggle::make('is_active')
                        ->label('Status Aktif')
                        ->default(true),
                ])->columns(2),

            // --- LOGIKA PENEMPATAN KERJA (TETAP SAMA) ---
            Forms\Components\Section::make('Penempatan Kerja')
                ->description('Lokasi unit kerja pegawai.')
                ->visible(function (Get $get) {
                    $roleId = $get('roles'); 
                    if (blank($roleId)) return false;
                    return Role::where('id', $roleId)->where('name', 'pegawai')->exists();
                })
                ->schema([
                    Forms\Components\Select::make('directorate_id')
                        ->relationship('directorate', 'name')
                        ->searchable()
                        ->preload()
                        ->live()
                        ->afterStateUpdated(fn (Set $set) => $set('unit_id', null))
                        ->required(), 

                    Forms\Components\Select::make('unit_id')
                        ->options(fn (Get $get) => 
                            Unit::where('directorate_id', $get('directorate_id'))->pluck('name', 'id')
                        )
                        ->searchable()
                        ->preload()
                        ->live()
                        ->afterStateUpdated(fn (Set $set) => $set('subunit_id', null))
                        ->required(),

                    Forms\Components\Select::make('subunit_id')
                        ->options(fn (Get $get) => 
                            Subunit::where('unit_id', $get('unit_id'))->pluck('name', 'id')
                        )
                        ->searchable()
                        ->preload()
                        ->required(),
                ])->columns(3),
        ]);
}
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama')
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                
                // Menampilkan Role di Tabel
                Tables\Columns\TextColumn::make('roles.name')
                    ->badge()
                    ->color('info')
                    ->label('Role'),

                // Menampilkan Unit Kerja (Nested)
                Tables\Columns\TextColumn::make('directorate.name')
                    ->label('Direktorat')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true), // Sembunyikan default biar gak penuh

                Tables\Columns\TextColumn::make('unit.name')
                    ->label('Unit')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                Tables\Columns\TextColumn::make('subunit.name')
                    ->label('Sub Unit')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // Filter berdasarkan Role
                Tables\Filters\SelectFilter::make('roles')
                    ->relationship('roles', 'name'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}