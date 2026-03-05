<?php

namespace App\Filament\Resources\RoleResource\Pages;

use BezhanSalleh\FilamentShield\Resources\RoleResource\Pages\EditRole as ShieldEditRole;
use App\Filament\Resources\RoleResource;
use Illuminate\Support\Arr;
use BezhanSalleh\FilamentShield\Support\Utils;
use Filament\Actions;

class EditRole extends ShieldEditRole
{
    protected static string $resource = RoleResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function beforeSave(): void
    {
        $name = $this->data['name'] ?? null;
        
        $existingRole = \Spatie\Permission\Models\Role::where('name', $name)
            ->where('id', '!=', $this->record->id)->first();

        if ($existingRole) {
            $status = $existingRole->is_active ? 'Aktif' : 'Tidak Aktif';
            \Filament\Notifications\Notification::make()
                ->danger()
                ->title('Gagal Menyimpan')
                ->body("Role **{$name}** sudah terdaftar dengan status {$status}.")
                ->persistent()
                ->send();
            throw new \Filament\Support\Exceptions\Halt();
        }

        // --- CASCADING DEACTIVATION UNTUK USER ---
        $isDeactivating = isset($this->data['is_active']) && $this->data['is_active'] === false;
        
        if ($isDeactivating && $this->record->is_active) {
            // Role is being deactivated, so deactivate all users who have this role
            $usersWithRole = \App\Models\User::role($this->record->name)->get();
            foreach ($usersWithRole as $user) {
                $user->update(['is_active' => false]);
            }
        }
    }

    protected function getActions(): array
    {
        // Hilangkan tombol hapus dari form edit
        return [];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->permissions = collect($data)
            ->filter(function ($permission, $key) {
                return ! in_array($key, ['name', 'guard_name', 'is_active', 'select_all', Utils::getTenantModelForeignKey()]);
            })
            ->values()
            ->flatten()
            ->unique();

        if (Arr::has($data, Utils::getTenantModelForeignKey())) {
            return Arr::only($data, ['name', 'guard_name', 'is_active', Utils::getTenantModelForeignKey()]);
        }

        return Arr::only($data, ['name', 'guard_name', 'is_active']);
    }
}
