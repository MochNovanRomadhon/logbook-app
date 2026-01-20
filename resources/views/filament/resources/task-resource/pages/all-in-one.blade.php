<x-filament-panels::page class="h-full">

    {{-- 1. FORM FILTER (Khusus Admin/Pengawas) --}}
    @if($isAdmin)
        <div class="p-6 bg-white dark:bg-gray-900 rounded-xl shadow border border-gray-200 dark:border-gray-800 mb-6">
            <h2 class="text-base font-semibold leading-6 text-gray-950 dark:text-white mb-4">
                Filter Pegawai
            </h2>
            
            <form wire:submit="search">
                {{ $this->form }}
                
                <div class="flex justify-end mt-4">
                    <x-filament::button type="submit" icon="heroicon-m-magnifying-glass">
                        Cari Data Pegawai
                    </x-filament::button>
                </div>
            </form>
        </div>
    @endif

    {{-- 2. DATA TABS & TABLES --}}
    @if($selectedUserId)
        
        {{-- Header Nama Pegawai (Jika Admin sedang melihat data orang lain) --}}
        @if($isAdmin)
             <div class="flex items-center gap-2 px-4 py-2 bg-primary-50 dark:bg-primary-900/20 text-primary-600 dark:text-primary-400 rounded-lg mb-4 border border-primary-100 dark:border-primary-800">
                <x-heroicon-m-user class="w-5 h-5"/>
                <span class="font-medium">Menampilkan data: 
                    <strong class="underline decoration-wavy">
                        {{ \App\Models\User::find($selectedUserId)?->name ?? 'User Tidak Ditemukan' }}
                    </strong>
                </span>
             </div>
        @endif

        {{-- Container Utama AlpineJS --}}
        <div x-data="{ activeTab: 'task' }" class="flex flex-col h-full space-y-4">
            
            {{-- Tombol Navigasi Tab --}}
            <div class="flex border-b border-gray-200 dark:border-gray-700 overflow-x-auto scrollbar-hide">
                <button @click="activeTab = 'task'" 
                    :class="activeTab === 'task' ? 'border-primary-500 text-primary-600 dark:text-primary-400 bg-gray-50 dark:bg-gray-800/50' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'"
                    class="px-6 py-3 border-b-2 font-medium transition flex items-center gap-2 whitespace-nowrap rounded-t-lg">
                    <x-heroicon-m-clipboard-document-list class="w-5 h-5"/>
                    Daftar Tugas (Task)
                </button>
                <button @click="activeTab = 'logbook'" 
                    :class="activeTab === 'logbook' ? 'border-primary-500 text-primary-600 dark:text-primary-400 bg-gray-50 dark:bg-gray-800/50' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'"
                    class="px-6 py-3 border-b-2 font-medium transition flex items-center gap-2 whitespace-nowrap rounded-t-lg">
                    <x-heroicon-m-book-open class="w-5 h-5"/>
                    Laporan Harian (Logbook)
                </button>
            </div>

            {{-- Isi Tab 1: Task --}}
            <div x-show="activeTab === 'task'" 
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-2"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 class="w-full">
                 
                 {{-- Wrapper Overflow agar tidak menabrak sidebar --}}
                 <div class="overflow-x-auto rounded-xl ring-1 ring-gray-950/5 dark:ring-white/10 bg-white dark:bg-gray-900 shadow-sm">
                    {{ $this->getTaskTable() }}
                 </div>
            </div>

            {{-- Isi Tab 2: Logbook --}}
            <div x-show="activeTab === 'logbook'" 
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-2"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 style="display: none;"
                 class="w-full">
                 
                 {{-- Wrapper Overflow agar tidak menabrak sidebar --}}
                 <div class="overflow-x-auto rounded-xl ring-1 ring-gray-950/5 dark:ring-white/10 bg-white dark:bg-gray-900 shadow-sm">
                    {{ $this->getLogbookTable() }}
                 </div>
            </div>
        </div>

    {{-- Tampilan Kosong (Placeholder) Jika Admin Belum Memilih --}}
    @elseif($isAdmin)
        <div class="flex flex-col items-center justify-center p-12 bg-white dark:bg-gray-900 rounded-xl mt-6 border border-dashed border-gray-300 dark:border-gray-700">
            <div class="text-gray-400 mb-4 bg-gray-50 dark:bg-gray-800 p-4 rounded-full">
                <x-heroicon-o-magnifying-glass class="w-12 h-12 mx-auto"/>
            </div>
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Belum ada data ditampilkan</h3>
            <p class="text-gray-500 dark:text-gray-400 mt-1">Silakan pilih Pegawai melalui filter di atas, lalu klik tombol "Cari Data".</p>
            <div class="text-gray-400 mb-4 bg-gray-50 dark:bg-gray-800 p-4 rounded-full">
                <x-heroicon-o-magnifying-glass class="w-12 h-12 mx-auto"/>
            </div>
        </div>
    @endif

</x-filament-panels::page>