<?php

namespace App\Filament\Resources\LogbookResource\Pages;

use App\Filament\Resources\LogbookResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditLogbook extends EditRecord
{
    protected static string $resource = LogbookResource::class;

    // 1. UBAH LABEL TOMBOL SAVE BAWAAN
    protected function getSaveFormAction(): Actions\Action
    {
        return parent::getSaveFormAction()
            ->label('Simpan Perubahan') // Ubah label
            ->icon('heroicon-o-check');
    }

    // 2. ATUR TOMBOL DI HEADER (DELETE & FINALISASI)
    protected function getHeaderActions(): array
    {
        return [
            // Tombol Delete (Hanya muncul jika belum final atau user adalah admin)
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
                ->visible(fn () => !$this->record->is_submitted) // Hanya muncul jika belum submit
                ->action(function () {
                    // Update status
                    $this->record->update(['is_submitted' => true]);
                    
                    Notification::make()
                        ->title('Logbook Berhasil Difinalisasi')
                        ->success()
                        ->send();
                    
                    // Refresh halaman agar form terkunci
                    $this->redirect($this->getResource()::getUrl('edit', ['record' => $this->record]));
                }),
        ];
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