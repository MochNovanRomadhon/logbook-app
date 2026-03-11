<?php

namespace App\Filament\Resources\UnitResource\Pages;

use App\Filament\Resources\UnitResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateUnit extends CreateRecord
{
    protected static string $resource = UnitResource::class;

    protected function beforeCreate(): void
    {
        $name = $this->data['name'];
        $directorateId = $this->data['directorate_id'];
        
        $existing = \App\Models\Unit::whereRaw('LOWER(name) = ?', [strtolower($name)])
            ->where('directorate_id', $directorateId)
            ->first();

        if ($existing) {
            \Filament\Notifications\Notification::make()
                ->danger()
                ->title('Data Duplikat')
                ->body('Unit dengan nama "' . $name . '" sudah ada. Gunakan nama yang berbeda.')
                ->persistent()
                ->send();

            throw new \Filament\Support\Exceptions\Halt();
        }
    }

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
            ->keyBindings(['mod+s']);
    }
}
