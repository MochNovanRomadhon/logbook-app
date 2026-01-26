<?php

namespace App\Filament\Resources\SubunitResource\Pages;

use App\Filament\Resources\SubunitResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSubunit extends EditRecord
{
    protected static string $resource = SubunitResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function getSaveFormAction(): \Filament\Actions\Action
    {
        return \Filament\Actions\Action::make('save')
            ->label('Simpan Perubahan')
            ->icon('heroicon-m-check')
            ->color('primary')
            ->action(fn () => $this->save())
            ->requiresConfirmation()
            ->modalHeading('Update Data')
            ->modalDescription('Apakah Anda yakin ingin menyimpan perubahan ini?')
            ->modalSubmitActionLabel('Ya, Update')
            ->modalCancelActionLabel('Batal')
            ->modalIcon('heroicon-o-pencil-square')
            ->modalIconColor('warning')
            ->keyBindings(['mod+s']);
    }
}
