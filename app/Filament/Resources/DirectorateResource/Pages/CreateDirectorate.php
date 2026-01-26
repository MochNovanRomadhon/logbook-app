<?php

namespace App\Filament\Resources\DirectorateResource\Pages;

use App\Filament\Resources\DirectorateResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateDirectorate extends CreateRecord
{
    protected static string $resource = DirectorateResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreateAnotherFormAction(): \Filament\Actions\Action
    {
        return \Filament\Actions\Action::make('createAnother')->hidden();
    }

    protected function getCreateFormAction(): \Filament\Actions\Action
    {
        return \Filament\Actions\Action::make('save')
            ->label('Simpan Data')
            ->icon('heroicon-m-check')
            ->color('primary')
            ->action(fn () => $this->create())
            ->requiresConfirmation()
            ->modalHeading('Simpan Data Baru')
            ->modalDescription('Apakah Anda yakin data yang dimasukkan sudah benar?')
            ->modalSubmitActionLabel('Ya, Simpan')
            ->modalCancelActionLabel('Batal')
            ->modalIcon('heroicon-o-check-circle')
            ->modalIconColor('success')
            ->keyBindings(['mod+s']);
    }
}
