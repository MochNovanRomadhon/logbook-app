<?php

namespace App\Filament\Resources\DirectorateResource\Pages;

use App\Filament\Resources\DirectorateResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateDirectorate extends CreateRecord
{
    protected static string $resource = DirectorateResource::class;

    protected function beforeCreate(): void
    {
        $name = $this->data['name'];
        $existing = \App\Models\Directorate::whereRaw('LOWER(name) = ?', [strtolower($name)])->first();

        if ($existing) {
            \Filament\Notifications\Notification::make()
                ->danger()
                ->title('Data Duplikat')
                ->body('Direktorat dengan nama "' . $name . '" sudah ada. Gunakan nama yang berbeda.')
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
