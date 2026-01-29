<?php

namespace App\Filament\Auth;

use Filament\Pages\Auth\Login;
use Illuminate\Validation\ValidationException;
use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Component;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Hash;
use App\Models\User; // Pastikan model User sesuai namespace Anda

class CustomLogin extends Login
{
    /**
     * Override authenticate method to provide specific error messages.
     */
    public function authenticate(): ?\Filament\Http\Responses\Auth\Contracts\LoginResponse
    {
        $data = $this->form->getState();

        $email = $data['email'];
        $password = $data['password'];

        // 1. Cek apakah email ada di database
        $user = User::where('email', $email)->first();

        if (! $user) {
            // Jika email tidak ditemukan
            throw ValidationException::withMessages([
                'data.email' => __('Email tidak terdaftar.'),
            ]);
        }

        // 2. Cek apakah password cocok
        if (! Hash::check($password, $user->password)) {
            // Jika password salah
            throw ValidationException::withMessages([
                'data.password' => __('Password yang Anda masukkan salah.'),
            ]);
        }
        
        // 3. Jika lolos pemeriksaan manual, lanjutkan ke proses login standar Filament
        //    Ini penting agar session, remember me, dll tetap berjalan normal.
        if (! filament()->auth()->attempt([
            'email' => $email,
            'password' => $password,
        ], $data['remember'] ?? false)) {
            
            // Fallback jika ada hal lain (misal user inactive tapi hash benar)
            throw ValidationException::withMessages([
                'data.email' => __('Login gagal. Silakan coba lagi.'),
            ]);
        }

        return app(\Filament\Http\Responses\Auth\Contracts\LoginResponse::class);
    }
}
