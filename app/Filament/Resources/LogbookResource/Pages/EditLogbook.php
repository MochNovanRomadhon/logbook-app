<?php

namespace App\Filament\Resources\LogbookResource\Pages;

use App\Filament\Resources\LogbookResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;
use Carbon\Carbon;

class EditLogbook extends EditRecord
{
    protected static string $resource = LogbookResource::class;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        $logbook = $this->record;

        // Cek jika logbook belum difinalisasi dan tanggalnya sudah lewat dari hari ini
        if (!$logbook->is_submitted && Carbon::parse($logbook->date)->isBefore(now()->startOfDay())) {

            // 1. Kunci otomatis
            $logbook->update(['is_submitted' => true]);

            // 2. Jalankan logika update status task dan cek progress kosong
            foreach ($logbook->items as $item) {
                if ($item->task_id) {
                    // Jika progress akhir (current) belum diisi (null), ambil dari (previous) atau 0
                    if (is_null($item->current_progress)) {
                        $item->update([
                            'current_progress' => $item->previous_progress ?? 0
                        ]);
                    }

                    $task = \App\Models\Task::find($item->task_id);
                    if ($task) {
                        if ($item->current_progress == 100) {
                            $task->update([
                                'status' => 'completed',
                                'completed_at' => now(),
                            ]);
                        } elseif ($task->status === 'pending') {
                            $task->update([
                                'status' => 'in_progress',
                                'processed_at' => now(),
                            ]);
                        }
                    }
                }
            }

            // 3. Tampilkan notifikasi bahwa sistem otomatis mengunci
            Notification::make()
                ->title('Logbook Otomatis Difinalkan')
                ->body('Logbook ini telah melewati batas hari pengerjaan dan otomatis dikunci oleh sistem.')
                ->warning()
                ->persistent()
                ->send();

            // 4. Redirect ulang halaman agar UI form (tombol save dll) ter-refresh dan terkunci
            redirect($this->getResource()::getUrl('edit', ['record' => $logbook->id]));
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [
            // Tombol Delete
            Actions\DeleteAction::make()
            ->visible(fn() => !$this->record->is_submitted || auth()->user()->hasRole('super_admin')),

            // TOMBOL FINALISASI MANUAL
            Actions\Action::make('finalize')
            ->label('Finalisasi Logbook')
            ->icon('heroicon-o-lock-closed')
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading('Finalisasi Logbook')
            ->modalDescription('Setelah difinalisasi, logbook tidak dapat diedit kembali. Apakah Anda yakin?')
            ->modalSubmitActionLabel('Ya, Finalisasi')
            ->visible(fn() => !$this->record->is_submitted)
            ->action(function () {
            $this->record->update(['is_submitted' => true]);

            // Logika update status tugas 100% atau Menunggu -> Proses
            foreach ($this->record->items as $item) {
                if ($item->task_id) {
                    $task = \App\Models\Task::find($item->task_id);
                    if ($task) {
                        if ($item->current_progress == 100) {
                            $task->update([
                                'status' => 'completed',
                                'completed_at' => now(),
                            ]);
                        } elseif ($task->status === 'pending') {
                            $task->update([
                                'status' => 'in_progress',
                                'processed_at' => now(),
                            ]);
                        }
                    }
                }
            }

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
            ->action(fn() => $this->save())
            ->keyBindings(['mod+s']);
    }

    // SEMBUNYIKAN TOMBOL SAVE JIKA SUDAH FINAL
    protected function getFormActions(): array
    {
        // Jika sudah submit, hilangkan tombol save (kosongkan array actions bawah)
        if ($this->record->is_submitted) {
            return [];
        }

        return parent::getFormActions();
    }
}