<x-filament-widgets::widget>
    <x-filament::section wire:poll.1s="updateData" class="!p-0">

        <div class="p-5 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-3xl shadow-sm transition-all duration-300 relative overflow-hidden">

            <!-- Bagian Atas: Identitas Mesin Sanding & Kecepatan -->
            <div class="flex items-start justify-between pb-3 border-b border-gray-100 dark:border-gray-800/80 mb-4">
                <div class="flex items-center gap-3">
                    <!-- Icon Mesin Minimalis -->
                    <div class="w-12 h-12 rounded-2xl flex items-center justify-center bg-blue-500/10 text-blue-600 dark:text-blue-400 border border-blue-500/20 shadow-sm">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                        </svg>
                    </div>
                    <div>
                        <span class="text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider block">Monitoring Produksi</span>
                        <span class="text-sm font-extrabold text-gray-900 dark:text-white block">
                            Mesin Sanding
                        </span>
                    </div>
                </div>

                <div class="text-right">
                    <span class="text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider block">Kecepatan</span>
                    <div class="flex items-baseline justify-end gap-1 mt-0.5">
                        <span class="text-lg font-extrabold font-mono tracking-tight transition-colors duration-300
                            {{ $statusMesin === 'ONLINE' ? ($kecepatanSinyal >= 50 ? 'text-emerald-600 dark:text-emerald-400' : ($kecepatanSinyal >= 20 ? 'text-amber-500 dark:text-amber-400' : 'text-red-500 dark:text-red-400')) : 'text-gray-400' }}">
                            {{ $statusMesin === 'ONLINE' ? $kecepatanSinyal : '0' }}
                        </span>
                        <span class="text-xs font-bold text-gray-400">Mbps</span>
                    </div>
                </div>
            </div>

            <!-- Bagian Sub-Info: Wi-Fi Network & SSID -->
            <div class="flex items-center justify-between mb-4 px-1 text-xs text-gray-500 dark:text-gray-400">
                <div class="flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.117 10.325a9.003 9.003 0 017.766 0M5.293 7.502a13.002 13.002 0 0113.414 0M12 17.512h.01M10.12 14.25a4.5 4.5 0 013.76 0"></path>
                    </svg>
                    <span>WiFi: <strong class="text-gray-700 dark:text-gray-300">{{ $wifiSSID }}</strong></span>
                </div>
            </div>

            <!-- Bagian Tengah: Dua Kolom Sensor -->
            <div class="mt-2 mb-5 grid grid-cols-1 sm:grid-cols-2 gap-4 items-center bg-gray-50 dark:bg-gray-800/50 p-4 rounded-2xl border border-gray-100 dark:border-gray-800">
                <!-- Sensor 1 -->
                <div>
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider block">Sensor 1</span>
                        @if($sensor1Error)
                        <span class="px-1.5 py-0.5 text-[9px] font-bold bg-red-500 text-white rounded animate-pulse">ERROR</span>
                        @endif
                    </div>
                    <div class="flex items-baseline gap-1.5 mt-0.5">
                        <span class="text-3xl font-black font-mono text-gray-900 dark:text-white tracking-tight">
                            {{ number_format($totalSanding) }}
                        </span>
                        <span class="text-xs font-bold text-gray-400 uppercase">pcs</span>
                    </div>
                    <span class="text-xs font-semibold text-gray-600 dark:text-gray-300 block mt-1">Modal Sanding</span>
                </div>

                <!-- Sensor 2 -->
                <div class="sm:border-l sm:border-gray-200 dark:sm:border-gray-700 sm:pl-4">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider block">Sensor 2</span>
                        @if($sensor2Error)
                        <span class="px-1.5 py-0.5 text-[9px] font-bold bg-red-500 text-white rounded animate-pulse">ERROR</span>
                        @endif
                    </div>
                    <div class="flex items-baseline gap-1.5 mt-0.5">
                        <span class="text-3xl font-black font-mono text-gray-900 dark:text-white tracking-tight">
                            {{ number_format($totalSanding2) }}
                        </span>
                        <span class="text-xs font-bold text-gray-400 uppercase">pcs</span>
                    </div>
                    <span class="text-xs font-semibold text-gray-600 dark:text-gray-300 block mt-1">Hasil Sanding</span>
                </div>
            </div>

            <!-- Bagian Tombol Reset Pilihan -->
            <div class="mb-4 flex flex-wrap justify-end gap-2">
                <x-filament::button
                    wire:click="resetCounter('sensor1')"
                    wire:confirm="Yakin ingin mereset Modal Sanding ke 0?"
                    color="gray"
                    size="xs"
                    :disabled="$statusMesin === 'OFFLINE'">
                    Reset Modal
                </x-filament::button>

                <x-filament::button
                    wire:click="resetCounter('sensor2')"
                    wire:confirm="Yakin ingin mereset Hasil Sanding ke 0?"
                    color="warning"
                    size="xs"
                    :disabled="$statusMesin === 'OFFLINE'">
                    Reset Hasil
                </x-filament::button>

                <x-filament::button
                    wire:click="resetCounter('all')"
                    wire:confirm="Yakin ingin mereset KEDUANYA ke 0?"
                    color="danger"
                    size="xs"
                    icon="heroicon-m-arrow-path"
                    :disabled="$statusMesin === 'OFFLINE'">
                    Reset Semua
                </x-filament::button>
            </div>

            <!-- Bagian Bawah: Status Online/Offline -->
            <div class="pt-3 border-t border-gray-100 dark:border-gray-800/80 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    @if($statusMesin === 'ONLINE')
                    <span class="relative flex h-3 w-3 items-center justify-center">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-500 shadow-sm shadow-emerald-500/50"></span>
                    </span>
                    <span class="text-xs font-extrabold tracking-wide text-emerald-600 dark:text-emerald-400">ONLINE</span>
                    @else
                    <span class="relative flex h-3 w-3 items-center justify-center">
                        <span class="absolute inline-flex h-full w-full rounded-full bg-red-400/30"></span>
                        <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-red-400 shadow-sm shadow-red-400/50"></span>
                    </span>
                    <span class="text-xs font-extrabold tracking-wide text-red-500 dark:text-red-400">OFFLINE</span>
                    @endif
                </div>

                <span class="text-[11px] font-medium text-gray-400">
                    {{ $statusMesin === 'ONLINE' ? 'ESP32 Connected' : 'ESP32 Disconnected' }}
                </span>
            </div>

        </div>
    </x-filament::section>
</x-filament-widgets::widget>