<x-filament-widgets::widget>
    <x-filament::section wire:poll.1s="updateData" class="!p-0">

        @php
        $isOnline = $statusMesin === 'ONLINE';

        // Meniru baris 0 LCD Arduino
        $net = $isOnline
        ? ['lan' => 'OK', 'mqtt' => 'OK', 'ok' => true]
        : ['lan' => 'OFF', 'mqtt' => 'NG', 'ok' => false];

        $netColor = $net['ok']
        ? 'text-emerald-600 dark:text-emerald-400'
        : 'text-red-500 dark:text-red-400';
        @endphp

        <div wire:click="navigateToRekap" class="p-4 sm:p-5 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-md shadow-sm transition-colors duration-150 relative overflow-hidden cursor-pointer hover:bg-gray-100/80 dark:hover:bg-gray-800/60 group">

            <!-- Bagian Atas: Identitas Mesin & Status Jaringan -->
            <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-3 pb-3 border-b border-gray-100 dark:border-gray-800/80 mb-4">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-md flex items-center justify-center bg-blue-500/10 text-blue-600 dark:text-blue-400 border border-blue-500/20 shadow-sm shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                    </div>
                    <div>
                        <span class="text-[10px] sm:text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider block">Monitoring Produksi</span>
                        <span class="text-sm sm:text-base font-extrabold text-gray-900 dark:text-white block group-hover:text-amber-500 transition-colors">
                            {{ $machineNama }}
                        </span>
                    </div>
                </div>

                <!-- Status Jaringan -->
                <div class="flex sm:flex-col items-center sm:items-end justify-between gap-1.5 bg-gray-50 dark:bg-gray-800/40 sm:bg-transparent p-2.5 sm:p-0 rounded-md">
                    <span class="text-[10px] sm:text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider block">Status Jaringan</span>
                    <div class="flex items-center gap-3 font-mono text-xs sm:text-sm font-extrabold tracking-tight {{ $netColor }}">
                        <span>LAN: {{ $net['lan'] }}</span>
                        <span>MQTT: {{ $net['mqtt'] }}</span>
                    </div>
                </div>
            </div>

            <!-- Bagian Tengah: Dua Kolom Sensor -->
            <div class="mt-2 mb-4 sm:mb-5 grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4 items-center bg-gray-50 dark:bg-gray-800/50 p-3.5 sm:p-4 rounded-md border border-gray-100 dark:border-gray-800 {{ $isOnline ? '' : 'opacity-60' }}">
                <div>
                    <span class="text-[10px] sm:text-[11px] font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider block">Sensor 1</span>
                    <div class="flex items-baseline gap-1.5 mt-0.5">
                        <span class="text-2xl sm:text-3xl font-black font-mono text-gray-900 dark:text-white tracking-tight">
                            {{ number_format($totalSensor1) }}
                        </span>
                        <span class="text-[10px] sm:text-xs font-bold text-gray-400 uppercase">{{ $satuan }}</span>
                    </div>
                    <span class="text-[11px] sm:text-xs font-semibold text-gray-600 dark:text-gray-300 block mt-1">{{ $labelSensor1 }}</span>
                    <span class="text-[10px] text-gray-500 dark:text-gray-400 block mt-1">Terakhir bertambah: {{ $terakhirSensor1 }}</span>
                    @if($infoSensor1)
                    <div class="mt-2 rounded-md border border-warning-500/30 bg-warning-500/10 px-2 py-1.5 text-[11px] font-semibold text-warning-700 dark:text-warning-400">
                        Perlu dicek: {{ $infoSensor1 }}
                    </div>
                    @endif
                </div>

                <div class="border-t sm:border-t-0 sm:border-l border-gray-200 dark:border-gray-700 pt-3 sm:pt-0 sm:pl-4">
                    <span class="text-[10px] sm:text-[11px] font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider block">Sensor 2</span>
                    <div class="flex items-baseline gap-1.5 mt-0.5">
                        <span class="text-2xl sm:text-3xl font-black font-mono text-gray-900 dark:text-white tracking-tight">
                            {{ number_format($totalSensor2) }}
                        </span>
                        <span class="text-[10px] sm:text-xs font-bold text-gray-400 uppercase">{{ $satuan }}</span>
                    </div>
                    <span class="text-[11px] sm:text-xs font-semibold text-gray-600 dark:text-gray-300 block mt-1">{{ $labelSensor2 }}</span>
                    <span class="text-[10px] text-gray-500 dark:text-gray-400 block mt-1">Terakhir bertambah: {{ $terakhirSensor2 }}</span>
                    @if($infoSensor2)
                    <div class="mt-2 rounded-md border border-warning-500/30 bg-warning-500/10 px-2 py-1.5 text-[11px] font-semibold text-warning-700 dark:text-warning-400">
                        Perlu dicek: {{ $infoSensor2 }}
                    </div>
                    @endif
                </div>
            </div>

            <!-- Bagian Tombol Reset -->
            <div class="mb-4 flex flex-col sm:flex-row justify-end gap-2" wire:click.stop>
                <x-filament::button
                    wire:click="resetCounter('sensor1')"
                    wire:confirm="Yakin ingin mereset {{ $labelSensor1 }} ke 0?"
                    color="warning"
                    size="xs"
                    class="w-full sm:w-auto justify-center"
                    :disabled="! $isOnline">
                    Reset {{ $labelSensor1 }}
                </x-filament::button>

                <x-filament::button
                    wire:click="resetCounter('sensor2')"
                    wire:confirm="Yakin ingin mereset {{ $labelSensor2 }} ke 0?"
                    color="gray"
                    size="xs"
                    class="w-full sm:w-auto justify-center"
                    :disabled="! $isOnline">
                    Reset {{ $labelSensor2 }}
                </x-filament::button>

                <x-filament::button
                    wire:click="resetCounter('all')"
                    wire:confirm="Yakin ingin mereset KEDUANYA ke 0?"
                    color="danger"
                    size="xs"
                    icon="heroicon-m-arrow-path"
                    class="w-full sm:w-auto justify-center"
                    :disabled="! $isOnline">
                    Reset Semua
                </x-filament::button>
            </div>

            <!-- Bagian Bawah: Status Online/Offline -->
            <div class="pt-3 border-t border-gray-100 dark:border-gray-800/80 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    @if($isOnline)
                    <span class="inline-flex rounded-full h-2.5 w-2.5 bg-emerald-500 animate-pulse"></span>
                    <span class="text-xs font-bold tracking-wide text-emerald-600 dark:text-emerald-400">ONLINE</span>
                    @else
                    <span class="inline-flex rounded-full h-2.5 w-2.5 bg-red-400"></span>
                    <span class="text-xs font-bold tracking-wide text-red-500 dark:text-red-400">OFFLINE</span>
                    @endif
                </div>

                <div class="flex items-center gap-1 text-xs text-amber-500 font-medium hover:underline">
                    <span>Lihat Rekapitulasi</span>
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </div>
            </div>

        </div>
    </x-filament::section>
</x-filament-widgets::widget>