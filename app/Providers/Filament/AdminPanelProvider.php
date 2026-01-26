<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Support\Facades\Blade; 
use Filament\View\PanelsRenderHook;
use Illuminate\Support\HtmlString; // <--- PENTING: Untuk render HTML di logo
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')

            // --- KONFIGURASI LOGIN ---
            ->login()
            ->passwordReset()
            ->profile()
            
            // --- 1. LOGIKA NAMA SISTEM (Untuk Judul Tab Browser) ---
            ->brandName(fn () => request()->routeIs('filament.admin.auth.login') 
                ? 'Sistem Logbook Harian' 
                : 'Logbook'
            )

            // --- 2. LOGIKA TAMPILAN HEADER (Logo & Teks) ---
            ->brandLogo(fn () => request()->routeIs('filament.admin.auth.login') 
                
                // KONDISI A: HALAMAN LOGIN
                // Return URL gambar saja (Logo Besar)
                ? asset('images/logo.png') 
                
                // KONDISI B: DASHBOARD / HEADER
                // Return HTML Campuran (Logo Kecil + Teks Nama Sistem)
                : new HtmlString('
                    <div class="flex items-center gap-3">
                        <img src="'.asset('images/favicon.png').'" alt="Logo" style="height: 2rem; width: auto;">
                        <span class="font-bold text-xl tracking-tight text-gray-950 dark:text-white">
                            Logbook
                        </span>
                    </div>
                ')
            )
            
            // Brand Logo Height dimatikan/dihapus karena tinggi sudah diatur manual di HTML di atas
            // ->brandLogoHeight('3rem') 
            
            ->favicon(asset('images/favicon.png'))

            // --- WARNA TEMA ---
            ->colors([
                'primary' => Color::hex('#05b9ad')
            ])
            ->sidebarCollapsibleOnDesktop()

            // --- HOOK 1: STYLES & CSS KHUSUS LOGIN ---
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn () => Blade::render('
                    {{-- Panggil CSS Custom Anda --}}
                    ' . view('filament.admin.styles')->render() . '
                    
                    {{-- CSS Tambahan: Memperbesar Logo HANYA di Halaman Login --}}
                    <style>
                        .fi-simple-main-header img {
                            height: 5rem !important; /* Atur tinggi logo Login disini */
                            width: auto;
                        }
                    </style>
                ')
            )

            // --- HOOK 2: SIDEBAR FOOTER (Profil Bawah) ---
            ->renderHook(
                PanelsRenderHook::SIDEBAR_NAV_END,
                fn () => view('filament.admin.sidebar-footer')
            )

            // --- HOOK 3: TEXT "SELAMAT DATANG" DI ATAS FORM LOGIN ---
            ->renderHook(
                PanelsRenderHook::AUTH_LOGIN_FORM_BEFORE,
                fn (): string => Blade::render(<<<blade
                    <div class="w-full text-left mb-6 -mt-4">
                        <p class="text-base font-normal text-gray-500 dark:text-gray-400">
                            Selamat Datang di
                        </p>
                        <h3 class="text-lg font-bold text-primary-600 dark:text-primary-400">
                            Sistem Logbook Harian
                        </h3>
                    </div>
                blade)
            )

            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                \App\Filament\Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                // Widgets\AccountWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->plugins([
                FilamentShieldPlugin::make(),
            ]);
    }
}