<?php

namespace App\Filament\Resources\TaskResource\Pages;

use App\Filament\Resources\TaskResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction; // Tambahkan ini jika ingin tombol hapus muncul di pojok atas

class EditTask extends EditRecord
{
    protected static string $resource = TaskResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    // --- 1. HAPUS FUNGSI getHeaderActions() YANG LAMA ---
    // Karena kita tidak butuh action tersembunyi lagi.
    // Jika Anda ingin tombol Delete (Hapus) standar ada di pojok kanan atas, 
    // gunakan kode di bawah ini. Jika tidak, hapus blok fungsi ini.
    protected function getHeaderActions(): array
    {
        return [
            // DeleteAction::make(),
        ];
    }

    // --- 2. PERBAIKAN TOMBOL SIMPAN (POPUP FIX) ---
    protected function getSaveFormAction(): Action
    {
        return Action::make('save')
            ->label('Simpan Perubahan')
            ->icon('heroicon-m-check') 
            ->color('primary')
            
            // PENTING: Panggil fungsi save bawaan Filament
            ->action(fn () => $this->save()) 

            
            ->keyBindings(['mod+s']);
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        if ($this->record->task_group_id) {
            $userIds = \App\Models\Task::where('task_group_id', $this->record->task_group_id)
                ->pluck('user_id')
                ->toArray();
            $data['user_ids'] = $userIds;
        } else {
            $data['user_ids'] = [$this->record->user_id];
        }

        return $data;
    }

    protected function beforeSave(): void
    {
        $title = $this->data['title'] ?? $this->record->title;
        $userIds = $this->data['user_ids'] ?? [$this->record->user_id];

        $duplicates = [];
        foreach ($userIds as $userId) {
            $query = \App\Models\Task::where('user_id', $userId)->where('title', $title);
            
            if ($this->record->task_group_id) {
                $query->where('task_group_id', '!=', $this->record->task_group_id);
            } else {
                $query->where('id', '!=', $this->record->id);
            }
            
            if ($query->exists()) {
                $pegawai = \App\Models\User::find($userId);
                $duplicates[] = $pegawai?->name ?? "User #{$userId}";
            }
        }

        if (!empty($duplicates)) {
            \Filament\Notifications\Notification::make()
                ->danger()
                ->title('Data Duplikat')
                ->body('Tugas dengan judul ini sudah ada untuk pegawai: ' . implode(', ', $duplicates) . '. Gunakan judul yang berbeda.')
                ->persistent()
                ->send();

            $this->halt();
        }
    }

    protected function handleRecordUpdate(\Illuminate\Database\Eloquent\Model $record, array $data): \Illuminate\Database\Eloquent\Model
    {
        $user = auth()->user();
        $userIds = $data['user_ids'] ?? [];
        unset($data['user_ids']);
        
        if ($user->hasRole('pengawas') && !empty($userIds)) {
            $taskGroupId = $record->task_group_id;
            
            if (!$taskGroupId && count($userIds) === 1) {
                $data['user_id'] = $userIds[0];
                $record->update($data);
                return $record;
            }
            
            if (!$taskGroupId && count($userIds) > 1) {
                $taskGroupId = (string) \Illuminate\Support\Str::uuid();
                $data['task_group_id'] = $taskGroupId; 
            }
            
            $record->update($data);
            
            if ($taskGroupId) {
                $existingTasks = \App\Models\Task::where('task_group_id', $taskGroupId)->get();
                $existingUserIds = $existingTasks->pluck('user_id')->toArray();
                
                $addedUsers = array_diff($userIds, $existingUserIds);
                $removedUsers = array_diff($existingUserIds, $userIds);
                
                if (in_array($record->user_id, $removedUsers)) {
                    $newOwnerId = array_values($userIds)[0]; 
                    
                    if (in_array($newOwnerId, $addedUsers)) {
                        $record->update(['user_id' => $newOwnerId]);
                        $addedUsers = array_diff($addedUsers, [$newOwnerId]);
                    } else {
                        \App\Models\Task::where('task_group_id', $taskGroupId)->where('user_id', $newOwnerId)->where('id', '!=', $record->id)->delete();
                        $record->update(['user_id' => $newOwnerId]);
                    }
                    $removedUsers = array_diff($removedUsers, [$record->user_id]); 
                }
                
                if (!empty($removedUsers)) {
                    \App\Models\Task::where('task_group_id', $taskGroupId)
                        ->whereIn('user_id', $removedUsers)
                        ->delete();
                }
                
                foreach ($addedUsers as $uId) {
                    $newTaskData = $record->toArray();
                    unset($newTaskData['id'], $newTaskData['created_at'], $newTaskData['updated_at']);
                    $newTaskData['user_id'] = $uId;
                    \App\Models\Task::create($newTaskData);
                }
                
                \App\Models\Task::where('task_group_id', $taskGroupId)
                    ->where('id', '!=', $record->id)
                    ->update($data);
                    
                return $record;
            }
        }
        
        $record->update($data);
        return $record;
    }
}