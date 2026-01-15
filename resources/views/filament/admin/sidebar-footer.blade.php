<div 
    x-data
    class="flex flex-col w-full border-t border-gray-200 dark:border-white/10 mt-auto transition-all duration-300"
>
    
    {{-- ========================================================== --}}
    {{-- LAYOUT 1: SIDEBAR TERTUTUP (COLLAPSED)                     --}}
    {{-- Hanya Avatar -> Klik muncul Popup -> Klik Info ke Profil   --}}
    {{-- ========================================================== --}}
    <div 
        x-show="! $store.sidebar.isOpen" 
        class="flex justify-center py-4"
        style="display: none;"
    >
        <x-filament::dropdown placement="right-end" telemetery="false">
            <x-slot name="trigger">
                <button 
                    type="button" 
                    class="shrink-0 flex items-center justify-center rounded-full hover:ring-2 hover:ring-primary-500 transition"
                    title="Profil Saya"
                >
                    <x-filament-panels::avatar.user size="w-9 h-9" :user="filament()->auth()->user()" />
                </button>
            </x-slot>

            {{-- ISI POPUP --}}
            <div class="p-3 space-y-3 min-w-[220px]">
                {{-- LINK KE PROFIL (Di dalam Popup) --}}
                <a href="{{ filament()->getProfileUrl() }}" class="flex items-center gap-4 border-b border-gray-100 pb-3 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 -mx-3 px-3 transition rounded-t-lg group">
                    <x-filament-panels::avatar.user size="w-10 h-10" :user="filament()->auth()->user()" />
                    <div class="flex flex-col text-left">
                        <span class="text-sm font-bold text-gray-950 dark:text-white group-hover:text-primary-600 truncate max-w-[160px]">
                            {{ filament()->auth()->user()->name }}
                        </span>
                        <span class="text-xs text-gray-500 dark:text-gray-400 truncate max-w-[160px]">
                            Ubah Profil / Password
                        </span>
                    </div>
                </a>

                {{-- TOMBOL LOGOUT --}}
                <form action="{{ filament()->getLogoutUrl() }}" method="post" class="w-full">
                    @csrf
                    <button type="submit" class="flex items-center w-full gap-2 px-3 py-2 text-sm font-medium text-danger-600 rounded-lg bg-danger-50 hover:bg-danger-100 dark:bg-danger-900/30 dark:text-danger-400 dark:hover:bg-danger-900/50 transition">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 2.062-5M12 12h9" />
                        </svg>
                        <span>Keluar Aplikasi</span>
                    </button>
                </form>
            </div>
        </x-filament::dropdown>
    </div>


    {{-- ========================================================== --}}
    {{-- LAYOUT 2: SIDEBAR TERBUKA (EXPANDED)                       --}}
    {{-- Avatar & Nama = Link Profil Langsung. Logout terpisah.     --}}
    {{-- ========================================================== --}}
    <div 
        x-show="$store.sidebar.isOpen"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-2"
        x-transition:enter-end="opacity-100 translate-y-0"
        class="flex items-center gap-x-3 px-4 py-4"
        style="display: none;"
    >
        {{-- LINK PROFIL (Area Kiri: Avatar + Nama) --}}
        <a 
            href="{{ filament()->getProfileUrl() }}" 
            class="flex items-center gap-3 min-w-0 flex-1 group hover:bg-gray-50 dark:hover:bg-white/5 -my-2 -ml-2 p-2 rounded-lg transition-colors"
            title="Ke Halaman Profil"
        >
            <x-filament-panels::avatar.user size="w-9 h-9" :user="filament()->auth()->user()" class="shrink-0" />

            <div class="flex flex-col min-w-0">
                <span class="text-sm font-bold text-gray-950 dark:text-white truncate group-hover:text-primary-600 transition-colors">
                    {{ filament()->auth()->user()->name }}
                </span>
                <span class="text-xs text-gray-500 dark:text-gray-400 truncate">
                    Edit Profil
                </span>
            </div>
        </a>

        {{-- TOMBOL LOGOUT (Area Kanan: Ikon Saja) --}}
        <form action="{{ filament()->getLogoutUrl() }}" method="post" class="shrink-0">
            @csrf
            <button 
                type="submit"
                title="Keluar"
                class="flex items-center justify-center w-9 h-9 text-gray-500 rounded-lg hover:bg-gray-100 hover:text-danger-600 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-danger-500 transition"
            >
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 2.062-5M12 12h9" />
                </svg>
            </button>
        </form>
    </div>

</div>