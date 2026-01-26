<x-filament-widgets::widget>
    <x-filament::section>
        {{-- Flex Column: Atas-Bawah. Text Center: Rata Tengah --}}
        <div class="flex flex-col items-center justify-center text-center py-6 gap-4">
            
            {{-- Bagian Ikon (Di Atas) --}}
            {{-- Ukuran w-16 h-16 agar proporsional, ikon di dalamnya w-10 h-10 --}}
            <div class="flex items-center justify-center w-16 h-16 rounded-full bg-primary-50 text-primary-600 dark:bg-primary-900/20">
                <x-heroicon-o-information-circle class="w-15 h-15" />
            </div>

            {{-- Bagian Teks (Di Bawah) --}}
            <div class="max-w-md"> 
                <h2 class="text-lg font-bold text-gray-800 dark:text-gray-100">
                    Menunggu Filter...
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">
                    Mohon pilih <strong>Direktorat > Unit > Sub Unit</strong> dan klik tombol <span class="font-medium text-primary-600">"Cari"</span> untuk menampilkan data.
                </p>
            </div>

        </div>
    </x-filament::section>
</x-filament-widgets::widget>