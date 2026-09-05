<x-filament-widgets::widget>
    <x-filament::section wire:poll.1s="updateData" class="!p-0">

        <div wire:click="navigateToRekap" class="p-5 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-md shadow-sm transition-colors duration-150 relative overflow-hidden cursor-pointer hover:bg-gray-100/80 dark:hover:bg-gray-800/60 group">

            <!-- Bagian Atas: Identitas Mesin & Kecepatan -->
            <div class="flex items-start justify-between pb-3 border-b border-gray-100 dark:border-gray-800/80 mb-4">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-md flex items-center justify-center bg-blue-500/10 text-blue-600 dark:text-blue-400 border border-blue-500/20 shadow-sm">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                    </div>
                    <div>
                        <span class="text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider block">Monitoring Produksi</span>
                        <span class="text-sm font-extrabold text-gray-900 dark:text-white block group-hover:text-amber-500 transition-colors">
                            {{ $machineNama }}
                        </span>
                    </div>
                </div>

                <div class="text-right">
                    <span class="text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider block">Kecepatan</span>
                    <div class="flex items-baseline justify-end gap-1 mt-0.5">
                        <span class="text-lg font-extrabold font-mono tracking-tight
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
            <div class="mt-2 mb-5 grid grid-cols-1 sm:grid-cols-2 gap-4 items-center bg-gray-50 dark:bg-gray-800/50 p-4 rounded-md border border-gray-100 dark:border-gray-800">
                <div>
                    <span class="text-[11px] font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider block">Sensor 1</span>
                    <div class="flex items-baseline gap-1.5 mt-0.5">
                        <span class="text-3xl font-black font-mono text-gray-900 dark:text-white tracking-tight">
                            {{ number_format($totalSanding) }}
                        </span>
                        <span class="text-xs font-bold text-gray-400 uppercase">pcs</span>
                    </div>
                    <span class="text-xs font-semibold text-gray-600 dark:text-gray-300 block mt-1">Modal Sanding</span>
                </div>

                <div class="sm:border-l sm:border-gray-200 dark:sm:border-gray-700 sm:pl-4">
                    <span class="text-[11px] font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider block">Sensor 2</span>
                    <div class="flex items-baseline gap-1.5 mt-0.5">
                        <span class="text-3xl font-black font-mono text-gray-900 dark:text-white tracking-tight">
                            {{ number_format($totalSanding2) }}
                        </span>
                        <span class="text-xs font-bold text-gray-400 uppercase">pcs</span>
                    </div>
                    <span class="text-xs font-semibold text-gray-600 dark:text-gray-300 block mt-1">Hasil Sanding</span>
                </div>
            </div>

            <!-- Bagian Tombol Reset Pilihan (wire:click.stop agar tidak memicu redirect card) -->
            <div class="mb-4 flex flex-wrap justify-end gap-2" wire:click.stop>
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
                <div class="flex items-center gap-2">
                    @if($statusMesin === 'ONLINE')
                    <span class="inline-flex rounded-full h-2.5 w-2.5 bg-emerald-500"></span>
                    <span class="text-xs font-bold tracking-wide text-emerald-600 dark:text-emerald-400">ONLINE</span>
                    @else
                    <span class="inline-flex rounded-full h-2.5 w-2.5 bg-red-400"></span>
                    <span class="text-xs font-bold tracking-wide text-red-500 dark:text-red-400">OFFLINE</span>
                    @endif
                </div>

                <div class="flex items-center gap-1 text-xs text-amber-500 font-medium">
                    <span>Lihat Rekapitulasi</span>
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </div>
            </div>

        </div>
    </x-filament::section>
</x-filament-widgets::widget>