<x-filament-widgets::widget>
    <x-filament::section>
        {{-- Judul Widget --}}
        <div class="mb-4">
            <h2 class="text-lg font-bold tracking-tight text-gray-950 dark:text-white">
                {{ $this->getHeading() }}
            </h2>
        </div>

        {{-- LOGIKA UTAMA --}}
        @if ($this->shouldShowChart())
            
            {{-- TAMPILAN GRAFIK --}}
            <div
                @if ($pollingInterval = $this->getPollingInterval())
                    wire:poll.{{ $pollingInterval }}="updateChartData"
                @endif
            >
                <div
                    wire:ignore
                    x-data="{
                        chart: null,
                        init() {
                            // PERBAIKAN: Menggunakan json_encode agar tidak error compile
                            let chartData = {{ json_encode($this->getData()) }};
                            let chartOptions = {{ json_encode($this->getOptions()) }} || {};

                            if (this.$refs.canvas) {
                                this.chart = new Chart(this.$refs.canvas, {
                                    type: '{{ $this->getType() }}',
                                    data: chartData,
                                    options: chartOptions,
                                });
                            }

                            // Watcher agar grafik update saat data berubah
                            this.$watch('$wire.data', (newData) => {
                                if (this.chart) {
                                    this.chart.data = newData;
                                    this.chart.update();
                                }
                            });
                        }
                    }"
                >
                    <canvas 
                        x-ref="canvas" 
                        style="max-height: {{ $this->getMaxHeight() }}; width: 100%;"
                    ></canvas>
                </div>
            </div>

        @else

            {{-- TAMPILAN TEXT INSTRUKSI --}}
            <div class="flex flex-col items-center justify-center py-6 text-center text-gray-500 bg-gray-50 rounded-lg dark:bg-gray-800 dark:text-gray-400">
                <x-heroicon-o-funnel class="w-12 h-12 mb-3 text-gray-400 dark:text-gray-600" />
                
                <h3 class="text-base font-medium text-gray-900 dark:text-gray-100">
                    Menunggu Filter Data
                </h3>
                
                <p class="mt-1 text-sm max-w-xs">
                    Silakan pilih <strong>Direktorat, Unit, atau Sub-unit</strong> pada filter di atas untuk melihat statistik.
                </p>
            </div>

        @endif
    </x-filament::section>
</x-filament-widgets::widget>