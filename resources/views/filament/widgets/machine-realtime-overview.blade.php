<x-filament-widgets::widget>
    <x-filament::section wire:poll.1s="updateData" class="!p-0 !bg-transparent !shadow-none">

        @php
        $isOnline = $statusMesin === 'ONLINE';

        $net = $isOnline
        ? ['lan' => 'OK', 'mqtt' => 'OK', 'ok' => true]
        : ['lan' => 'OFF', 'mqtt' => 'NG', 'ok' => false];

        $netColor = $net['ok']
        ? 'text-emerald-600 dark:text-emerald-400'
        : 'text-red-500 dark:text-red-400';
        @endphp

        <div wire:click="navigateToRekap" class="p-4 sm:p-5 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-lg shadow-xs transition-colors duration-150 relative overflow-hidden cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-800/40 group">

            <!-- Bagian Atas: Identitas Mesin & Status Jaringan -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-zinc-100 dark:border-zinc-800/80 mb-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-md flex items-center justify-center bg-zinc-100 dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 border border-zinc-200/60 dark:border-zinc-700/60 shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                    </div>
                    <div>
                        <span class="text-[11px] font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider block">Monitoring Produksi</span>
                        <span class="text-sm sm:text-base font-bold text-zinc-900 dark:text-zinc-100 tracking-tight block">
                            {{ $machineNama }}
                        </span>
                    </div>
                </div>

                <!-- Status Jaringan -->
                <div class="w-full sm:w-auto flex items-center justify-between sm:justify-start gap-2.5 bg-zinc-50 dark:bg-zinc-800/50 px-3 py-1.5 rounded-md border border-zinc-200/60 dark:border-zinc-800">
                    <span class="text-[11px] font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Jaringan</span>
                    <div class="flex items-center gap-2 font-mono text-xs font-bold {{ $netColor }}">
                        <span class="px-1.5 py-0.5 rounded bg-white dark:bg-zinc-900">LAN: {{ $net['lan'] }}</span>
                        <span class="px-1.5 py-0.5 rounded bg-white dark:bg-zinc-900">MQTT: {{ $net['mqtt'] }}</span>
                    </div>
                </div>
            </div>

            <!-- Bagian Tengah: Sensor Layout (Row di Mobile, Grid di Tablet+) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4 items-stretch">

                <!-- Sensor 1 -->
                <div class="bg-zinc-50/70 dark:bg-zinc-800/30 p-3.5 rounded-md border border-zinc-200/70 dark:border-zinc-800 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Sensor 1</span>
                            <span class="text-[11px] font-medium text-zinc-400 dark:text-zinc-500">{{ $labelSensor1 }}</span>
                        </div>
                        <div class="flex items-baseline gap-1.5 my-1 sm:my-2">
                            <span class="text-xl sm:text-3xl font-bold font-mono text-zinc-900 dark:text-zinc-100 tracking-tight">
                                {{ number_format($totalSensor1) }}
                            </span>
                            <span class="text-xs font-semibold text-zinc-400 dark:text-zinc-500 uppercase">{{ $satuan }}</span>
                        </div>
                        <!-- Informasi Waktu Terakhir Dimunculkan Kembali -->
                        <span class="text-[11px] text-zinc-500 dark:text-zinc-400 block mt-1">Terakhir: {{ $terakhirSensor1 }}</span>
                    </div>
                </div>

                <!-- Sensor 2 -->
                <div class="bg-zinc-50/70 dark:bg-zinc-800/30 p-3.5 rounded-md border border-zinc-200/70 dark:border-zinc-800 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Sensor 2</span>
                            <span class="text-[11px] font-medium text-zinc-400 dark:text-zinc-500">{{ $labelSensor2 }}</span>
                        </div>
                        <div class="flex items-baseline gap-1.5 my-1 sm:my-2">
                            <span class="text-xl sm:text-3xl font-bold font-mono text-zinc-900 dark:text-zinc-100 tracking-tight">
                                {{ number_format($totalSensor2) }}
                            </span>
                            <span class="text-xs font-semibold text-zinc-400 dark:text-zinc-500 uppercase">{{ $satuan }}</span>
                        </div>
                        <!-- Informasi Waktu Terakhir Dimunculkan Kembali -->
                        <span class="text-[11px] text-zinc-500 dark:text-zinc-400 block mt-1">Terakhir: {{ $terakhirSensor2 }}</span>
                    </div>
                </div>

            </div>

            <!-- Bagian Tombol Reset: 3 baris penuh di mobile, rata kanan di tablet+ -->
            <div class="mb-4 grid grid-cols-1 gap-2 sm:flex sm:flex-wrap sm:items-center sm:justify-end" wire:click.stop>
                <x-filament::button
                    wire:click="resetCounter('sensor1')"
                    wire:confirm="Yakin ingin mereset {{ $labelSensor1 }} ke 0?"
                    color="gray"
                    size="xs"
                    class="w-full sm:w-auto justify-center rounded-md border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-700/80 shadow-2xs"
                    :disabled="! $isOnline">
                    Reset {{ $labelSensor1 }}
                </x-filament::button>

                <x-filament::button
                    wire:click="resetCounter('sensor2')"
                    wire:confirm="Yakin ingin mereset {{ $labelSensor2 }} ke 0?"
                    color="gray"
                    size="xs"
                    class="w-full sm:w-auto justify-center rounded-md border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-700/80 shadow-2xs"
                    :disabled="! $isOnline">
                    Reset {{ $labelSensor2 }}
                </x-filament::button>

                <x-filament::button
                    wire:click="resetCounter('all')"
                    wire:confirm="Yakin ingin mereset KEDUANYA ke 0?"
                    color="gray"
                    size="xs"
                    icon="heroicon-m-arrow-path"
                    class="w-full sm:w-auto justify-center rounded-md bg-zinc-900 dark:bg-zinc-50 text-white dark:text-zinc-950 hover:bg-zinc-800 dark:hover:bg-zinc-200 shadow-2xs font-medium"
                    :disabled="! $isOnline">
                    Reset Semua
                </x-filament::button>
            </div>

            <!-- Bagian Bawah: Status Online/Offline & Tautan Rekapitulasi -->
            <div class="pt-3 border-t border-zinc-100 dark:border-zinc-800/80 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    @if($isOnline)
                    <span class="relative flex h-2.5 w-2.5">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-500"></span>
                    </span>
                    <span class="text-xs font-bold tracking-wide text-emerald-600 dark:text-emerald-400">ONLINE</span>
                    @else
                    <span class="inline-flex rounded-full h-2.5 w-2.5 bg-red-500"></span>
                    <span class="text-xs font-bold tracking-wide text-red-600 dark:text-red-400">OFFLINE</span>
                    @endif
                </div>

                <div class="flex items-center gap-1.5 text-xs font-semibold text-zinc-700 dark:text-zinc-300">
                    <span>Lihat Rekapitulasi</span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </div>
            </div>

        </div>
    </x-filament::section>
</x-filament-widgets::widget>