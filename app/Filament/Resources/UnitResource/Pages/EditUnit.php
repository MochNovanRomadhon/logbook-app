<?php

namespace App\Filament\Resources\UnitResource\Pages;

use App\Filament\Resources\UnitResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUnit extends EditRecord
{
    protected static string $resource = UnitResource::class;

    protected function beforeSave(): void
    {
        $name = $this->data['name'];
        $directorateId = $this->data['directorate_id'];
        
        $existing = \App\Models\Unit::whereRaw('LOWER(name) = ?', [strtolower($name)])
            ->where('directorate_id', $directorateId)
            ->where('id', '!=', $this->record->id)
            ->first();

        if ($existing) {
            $status = $existing->is_active ? 'Aktif' : 'Tidak Aktif';
            \Filament\Notifications\Notification::make()
                ->danger()
                ->title('Gagal Menyimpan')
                ->body("**{$name}** sudah tersimpan pada Direktorat ini dengan status {$status}.")
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
