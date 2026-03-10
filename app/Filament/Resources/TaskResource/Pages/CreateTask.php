<?php

namespace App\Filament\Resources\TaskResource\Pages;

use App\Filament\Resources\TaskResource;
use App\Models\Task;
use Filament\Resources\Pages\CreateRecord;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class CreateTask extends CreateRecord
{
    protected static string $resource = TaskResource::class;

    // Override: Buat satu task per user yang dipilih (multiple pegawai)
    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        $user = Auth::user();

        // Ambil user_ids dari form (multi-select) lalu hapus dari data
        $userIds = $data['user_ids'] ?? [];
        unset($data['user_ids']);

        if ($user->hasRole('pengawas') && !empty($userIds)) {
            // Cek duplikasi: apakah ada pegawai yang sudah memiliki tugas dengan judul yang sama?
            $duplicates = [];
            foreach ($userIds as $userId) {
                if (Task::where('user_id', $userId)->where('title', $data['title'])->exists()) {
                    $pegawai = \App\Models\User::find($userId);
                    $duplicates[] = $pegawai?->name ?? "User #{$userId}";
                }
            }

            if (!empty($duplicates)) {
                Notification::make()
                    ->danger()
                    ->title('Data Duplikat')
                    ->body('Tugas dengan judul ini sudah ada untuk pegawai: ' . implode(', ', $duplicates) . '. Gunakan judul yang berbeda.')
                    ->persistent()
                    ->send();

                // Hentikan proses simpan dengan exception yang akan dicatch Filament
                $this->halt();
            }

            // Pengawas menugaskan ke beberapa pegawai
            $data['assigned_by'] = $user->id;

            // Buat task_group_id jika lebih dari 1 pegawai
            $taskGroupId = count($userIds) > 1 ? (string) Str::uuid() : null;

            $firstTask = null;
            foreach ($userIds as $userId) {
                $taskData = array_merge($data, [
                    'user_id'       => $userId,
                    'task_group_id' => $taskGroupId,
                ]);
                $task = Task::create($taskData);
                if (!$firstTask) {
                    $firstTask = $task;
                }
            }

            return $firstTask;
        }

        // Pegawai membuat tugas untuk diri sendiri
        // Cek duplikasi
        if (Task::where('user_id', $user->id)->where('title', $data['title'])->exists()) {
            Notification::make()
                ->danger()
                ->title('Data Duplikat')
                ->body('Anda sudah memiliki tugas dengan judul "' . $data['title'] . '". Gunakan judul yang berbeda.')
                ->persistent()
                ->send();

            $this->halt();
        }

        $data['user_id'] = $user->id;
        return Task::create($data);
    }

    // Redirect setelah simpan
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    // Hilangkan tombol "Simpan & Buat Lagi"
    protected function getCreateAnotherFormAction(): Action
    {
        return Action::make('createAnother')->hidden();
    }

    // Tombol simpan
    protected function getCreateFormAction(): Action
    {
        return Action::make('save') 
            ->label('Simpan Data')
            ->icon('heroicon-m-check')
            ->color('primary')
            ->action(fn () => $this->create())
            ->keyBindings(['mod+s']);
    }
}