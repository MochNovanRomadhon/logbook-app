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
