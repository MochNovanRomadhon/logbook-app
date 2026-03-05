<?php

namespace App\Filament\Resources\RoleResource\Pages;

use BezhanSalleh\FilamentShield\Resources\RoleResource\Pages\CreateRole as ShieldCreateRole;
use App\Filament\Resources\RoleResource;
use Illuminate\Support\Arr;
use BezhanSalleh\FilamentShield\Support\Utils;

class CreateRole extends ShieldCreateRole
{
    protected static string $resource = RoleResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function beforeCreate(): void
    {
        $name = $this->data['name'] ?? null;
        
        $existingRole = \Spatie\Permission\Models\Role::where('name', $name)->first();

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
    }

    protected function mutateFormDataBeforeCreate(array $data): array
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
