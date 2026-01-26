<?php

namespace App\Filament\Resources\TaskResource\Pages;

use App\Filament\Resources\TaskResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction; // Tambahkan ini jika ingin tombol hapus muncul di pojok atas

class EditTask extends EditRecord
{
    protected static string $resource = TaskResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    // --- 1. HAPUS FUNGSI getHeaderActions() YANG LAMA ---
    // Karena kita tidak butuh action tersembunyi lagi.
    // Jika Anda ingin tombol Delete (Hapus) standar ada di pojok kanan atas, 
    // gunakan kode di bawah ini. Jika tidak, hapus blok fungsi ini.
    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    // --- 2. PERBAIKAN TOMBOL SIMPAN (POPUP FIX) ---
    protected function getSaveFormAction(): Action
    {
        return Action::make('save')
            ->label('Simpan Perubahan')
            ->icon('heroicon-m-check') 
            ->color('primary')
            
            // PENTING: Panggil fungsi save bawaan Filament
            ->action(fn () => $this->save()) 
            
            // --- Konfigurasi Popup ---
            ->requiresConfirmation()
            ->modalHeading('Update Data')
            ->modalDescription('Apakah Anda yakin ingin menyimpan perubahan ini?')
            ->modalSubmitActionLabel('Ya, Update')
            ->modalCancelActionLabel('Batal')
            ->modalIcon('heroicon-o-pencil-square')
            ->modalIconColor('warning') // Warna kuning (warning) karena ini edit
            
            ->keyBindings(['mod+s']);
    }
}