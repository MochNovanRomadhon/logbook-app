<?php

namespace App\Filament\Resources\SubunitResource\Pages;

use App\Filament\Resources\SubunitResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateSubunit extends CreateRecord
{
    protected static string $resource = SubunitResource::class;

    protected function beforeCreate(): void
    {
        $name = $this->data['name'];
        $unitId = $this->data['unit_id'];
        
        $existing = \App\Models\Subunit::whereRaw('LOWER(name) = ?', [strtolower($name)])
            ->where('unit_id', $unitId)
            ->first();

        if ($existing) {
            $status = $existing->is_active ? 'Aktif' : 'Tidak Aktif';
            \Filament\Notifications\Notification::make()
                ->danger()
                ->title('Gagal Menyimpan')
                ->body("{$name} sudah tersimpan pada Unit ini dengan status {$status}.")
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
