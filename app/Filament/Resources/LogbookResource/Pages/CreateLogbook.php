<?php

namespace App\Filament\Resources\LogbookResource\Pages;

use App\Filament\Resources\LogbookResource;
use App\Models\Logbook;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class CreateLogbook extends CreateRecord
{
    protected static string $resource = LogbookResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreateAnotherFormAction(): \Filament\Actions\Action
    {
        return \Filament\Actions\Action::make('createAnother')->hidden();
    }

    protected function beforeCreate(): void
    {
        $data = $this->form->getState();
        
        $exists = Logbook::where('user_id', Auth::id())
            ->whereDate('date', $data['date'])
            ->exists();

        if ($exists) {
            Notification::make()
                ->danger()
                ->title('Data Sudah Ada')
                ->body('Logbook untuk tanggal ini sudah ada. Silakan pilih tanggal lain atau edit logbook yang sudah ada.')
                ->persistent()
                ->send();

            $this->halt();
        }
    }

    protected function getCreateFormAction(): \Filament\Actions\Action
    {
        return \Filament\Actions\Action::make('save')
            ->label('Simpan Data')
            ->icon('heroicon-m-check')
            ->color('primary')
            // ->requiresConfirmation()
            // ->modalHeading('Konfirmasi Penyimpanan')
            // ->modalDescription('Apakah Anda yakin ingin menyimpan data logbook ini?')
            // ->modalSubmitActionLabel('Ya, Simpan')
            ->action(fn () => $this->create())
            ->keyBindings(['mod+s']);
    }
}
