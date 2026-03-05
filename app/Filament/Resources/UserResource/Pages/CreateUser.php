<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function beforeCreate(): void
    {
        $name = $this->data['name'] ?? null;
        $email = $this->data['email'] ?? null;
        
        $existingUser = \App\Models\User::where(function($query) use ($name, $email) {
            $query->where('name', $name)->orWhere('email', $email);
        })->first();

        if ($existingUser) {
            $status = $existingUser->is_active ? 'Aktif' : 'Tidak Aktif';
            $field = $existingUser->name === $name ? 'Nama' : 'Email';
            \Filament\Notifications\Notification::make()
                ->danger()
                ->title('Gagal Menyimpan')
                ->body("{$field} pengguna sudah terdaftar dengan status {$status}.")
                ->persistent()
                ->send();
            throw new \Filament\Support\Exceptions\Halt();
        }
    }

    protected function getCreateAnotherFormAction(): \Filament\Actions\Action
    {
        return \Filament\Actions\Action::make('createAnother')->hidden();
    }

    protected function getCreateFormAction(): \Filament\Actions\Action
    {
        return \Filament\Actions\Action::make('save')
            ->label('Simpan Data')
            ->icon('heroicon-m-check')
            ->color('primary')
            ->action(fn () => $this->create())
            ->keyBindings(['mod+s']);
    }
}
