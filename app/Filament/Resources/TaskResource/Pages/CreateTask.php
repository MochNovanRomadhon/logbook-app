<?php

namespace App\Filament\Resources\TaskResource\Pages;

use App\Filament\Resources\TaskResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Actions\Action;
use Illuminate\Support\Facades\Auth;

class CreateTask extends CreateRecord
{
    protected static string $resource = TaskResource::class;

    // 1. SOLUSI SQL ERROR (User ID)
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = Auth::id();
        return $data;
    }

    // 2. Redirect setelah simpan
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    // 3. Hilangkan tombol "Simpan & Buat Lagi"
    protected function getCreateAnotherFormAction(): Action
    {
        return Action::make('createAnother')->hidden();
    }

    // 4. SOLUSI POPUP TIDAK MUNCUL
    // Kita buat Action baru sepenuhnya (bukan edit parent) agar tidak dianggap tombol submit HTML biasa
    protected function getCreateFormAction(): Action
    {
        return Action::make('save') 
            ->label('Simpan Data')
            ->icon('heroicon-m-check')
            ->color('primary')
            // PENTING: Jangan pakai ->submit('create'), tapi pakai ->action()
            // Ini mencegah form langsung tersubmit sebelum popup
            ->action(fn () => $this->create()) 
            
            // Konfigurasi Popup
            ->requiresConfirmation()
            ->modalHeading('Simpan Data Baru')
            ->modalDescription('Apakah Anda yakin data yang dimasukkan sudah benar?')
            ->modalSubmitActionLabel('Ya, Simpan')
            ->modalCancelActionLabel('Batal')
            ->modalIcon('heroicon-o-check-circle')
            ->modalIconColor('success')
            
            // Tombol shortcut keyboard (Optional)
            ->keyBindings(['mod+s']);
    }
}