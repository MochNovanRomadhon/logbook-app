<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/admin'); // Opsional: Arahkan halaman depan langsung ke admin
});

// --- TAMBAHKAN INI UNTUK MEMPERBAIKI ERROR ---
Route::get('/login', function () {
    return redirect()->route('filament.admin.auth.login');
})->name('login');