<?php

namespace App\Filament\Resources\TaskResource\Pages;

use App\Filament\Resources\TaskResource;
use App\Models\Task;
use Filament\Resources\Pages\CreateRecord;
use Filament\Actions\Action;
use Illuminate\Support\Facades\Auth;

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
            // Pengawas menugaskan ke beberapa pegawai
            $data['assigned_by'] = $user->id;

            $firstTask = null;
            foreach ($userIds as $userId) {
                $taskData = array_merge($data, ['user_id' => $userId]);
                $task = Task::create($taskData);
                if (!$firstTask) {
                    $firstTask = $task;
                }
            }

            return $firstTask;
        }

        // Pegawai membuat tugas untuk diri sendiri
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

    // Popup konfirmasi sebelum simpan
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