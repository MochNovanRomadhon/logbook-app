<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// --- TAMBAHKAN INI UNTUK MEMPERBAIKI ERROR ---
Route::get('/login', function () {
    return redirect()->route('filament.admin.auth.login');
})->name('login');