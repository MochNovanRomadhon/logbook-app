<?php

namespace App\Filament\Resources\SubunitResource\Pages;

use App\Filament\Resources\SubunitResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSubunit extends EditRecord
{
    protected static string $resource = SubunitResource::class;

    protected function beforeSave(): void
    {
        $name = $this->data['name'];
        $unitId = $this->data['unit_id'];
        
        $existing = \App\Models\Subunit::whereRaw('LOWER(name) = ?', [strtolower($name)])
            ->where('unit_id', $unitId)
            ->where('id', '!=', $this->record->id)
            ->first();

        if ($existing) {
            \Filament\Notifications\Notification::make()
                ->danger()
                ->title('Data Duplikat')
                ->body('Sub Unit dengan nama "' . $name . '" sudah ada. Gunakan nama yang berbeda.')
                ->persistent()
                ->send();

            throw new \Filament\Support\Exceptions\Halt();
        }
    }

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
            ->keyBindings(['mod+s']);
    }
}
