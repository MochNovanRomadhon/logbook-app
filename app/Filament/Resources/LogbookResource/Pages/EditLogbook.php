<?php

namespace App\Filament\Resources\LogbookResource\Pages;

use App\Filament\Resources\LogbookResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditLogbook extends EditRecord
{
    protected static string $resource = LogbookResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [
            // Tombol Delete
            Actions\DeleteAction::make()
                ->visible(fn () => !$this->record->is_submitted || auth()->user()->hasRole('super_admin')),

            // TOMBOL FINALISASI
            Actions\Action::make('finalize')
                ->label('Finalisasi & Kunci Logbook')
                ->icon('heroicon-o-lock-closed')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Finalisasi Logbook')
                ->modalDescription('Setelah difinalisasi, logbook tidak dapat diedit kembali. Apakah Anda yakin?')
                ->modalSubmitActionLabel('Ya, Kunci Logbook')
                ->visible(fn () => !$this->record->is_submitted)
                ->action(function () {
                    $this->record->update(['is_submitted' => true]);
                    Notification::make()->title('Logbook Berhasil Difinalisasi')->success()->send();
                    $this->redirect($this->getResource()::getUrl('edit', ['record' => $this->record]));
                }),
        ];
    }
    
    protected function getSaveFormAction(): Actions\Action
    {
        return Actions\Action::make('save')
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

    // 3. SEMBUNYIKAN TOMBOL SAVE JIKA SUDAH FINAL
    protected function getFormActions(): array
    {
        // Jika sudah submit, hilangkan tombol save (kosongkan array actions bawah)
        if ($this->record->is_submitted) {
            return [];
        }

        return parent::getFormActions();
    }
}