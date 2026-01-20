<?php

namespace App\Filament\Resources\TaskResource\Pages;

use App\Filament\Resources\TaskResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateTask extends CreateRecord
{
    protected static string $resource = TaskResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Set assigned_by ke user yang login
        $data['assigned_by'] = Auth::id();

        // Jika user_id belum diisi (karena user biasa tidak lihat input user_id),
        // set ke diri sendiri
        if (!isset($data['user_id'])) {
            $data['user_id'] = Auth::id();
        }

        return $data;
    }
}