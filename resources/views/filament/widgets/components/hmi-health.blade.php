@php
$isOnline = $statusMesin === 'ONLINE';
$firmwareVersion = $firmwareVersion ?? 'v2026.10.17';
$newFirmwareAvailable = $newFirmwareAvailable ?? true;
$newFirmwareVersion = $newFirmwareVersion ?? 'v2026.10.19';
$uptimeString = $uptimeString ?? '12 Jam 42 Menit';
$freeHeapKb = $freeHeapKb ?? '184 KB';
$macAddress = $macAddress ?? 'DE:AD:BE:EF:FE:01';
@endphp

<div class="p-4 sm:p-5 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-lg shadow-xs space-y-4">
    <!-- Header HMI -->
    <div class="flex items-center justify-between pb-3 border-b border-zinc-100 dark:border-zinc-800/80">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-md flex items-center justify-center bg-zinc-100 dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 border border-zinc-200/60 dark:border-zinc-700/60 shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                </svg>
            </div>
            <div>
                <span class="text-[11px] font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider block">HMI Health Management</span>
                <span class="text-sm sm:text-base font-bold text-zinc-900 dark:text-zinc-100 tracking-tight block">{{ $machineNama }}</span>
                <span class="text-[10px] font-mono text-zinc-400 dark:text-zinc-500">MAC: {{ $macAddress }}</span>
            </div>
        </div>

        <div>
            @if($isOnline)
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-md text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200/80 dark:bg-emerald-500/10 dark:text-emerald-400 dark:border-emerald-500/20"><span class="w-2 h-2 rounded-full bg-emerald-500"></span> ONLINE</span>
            @else
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-md text-xs font-bold bg-red-50 text-red-700 border border-red-200/80 dark:bg-red-500/10 dark:text-red-400 dark:border-red-500/20"><span class="w-2 h-2 rounded-full bg-red-500"></span> OFFLINE</span>
            @endif
        </div>
    </div>

    <!-- Metrik Uptime & RAM -->
    <div class="grid grid-cols-2 gap-3">
        <div class="bg-zinc-50/70 dark:bg-zinc-800/30 p-3 rounded-md border border-zinc-200/70 dark:border-zinc-800 text-center">
            <span class="text-[10px] font-medium text-zinc-400 dark:text-zinc-500 uppercase tracking-wider block mb-1">Uptime</span>
            <span class="text-xs sm:text-sm font-bold font-mono text-zinc-800 dark:text-zinc-200">{{ $uptimeString }}</span>
        </div>
        <div class="bg-zinc-50/70 dark:bg-zinc-800/30 p-3 rounded-md border border-zinc-200/70 dark:border-zinc-800 text-center">
            <span class="text-[10px] font-medium text-zinc-400 dark:text-zinc-500 uppercase tracking-wider block mb-1">Free Heap (RAM)</span>
            <span class="text-xs sm:text-sm font-bold font-mono text-zinc-800 dark:text-zinc-200">{{ $freeHeapKb }}</span>
        </div>
    </div>

    <!-- Informasi Detail Status -->
    <div class="divide-y divide-zinc-100 dark:divide-zinc-800/60 text-xs font-medium">
        <div class="py-2.5 flex items-center justify-between"><span class="text-zinc-500 dark:text-zinc-400">Status Jaringan (LAN):</span><span class="text-emerald-600 dark:text-emerald-400 font-mono">Terhubung (Link OK)</span></div>
        <div class="py-2.5 flex items-center justify-between"><span class="text-zinc-500 dark:text-zinc-400">Status MQTT Broker:</span><span class="text-emerald-600 dark:text-emerald-400 font-mono">Connected (Broker OK)</span></div>
        <div class="py-2.5 flex items-center justify-between"><span class="text-zinc-500 dark:text-zinc-400">Status HTTP Server:</span><span class="text-emerald-600 dark:text-emerald-400 font-mono">Responsive (200 OK)</span></div>
        <div class="py-2.5 flex items-center justify-between"><span class="text-zinc-500 dark:text-zinc-400">Versi Firmware Saat Ini:</span><span class="text-zinc-800 dark:text-zinc-200 font-mono font-bold">{{ $firmwareVersion }}</span></div>
    </div>

    <!-- Panel OTA Update Stabil (Tanpa Berkedip) -->
    @if($newFirmwareAvailable)
    <div class="p-3.5 rounded-md bg-amber-500/10 border border-amber-500/20 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <div class="text-amber-600 dark:text-amber-400 shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                </svg>
            </div>
            <div>
                <div class="text-xs font-bold text-amber-800 dark:text-amber-300">Pembaruan Firmware Tersedia!</div>
                <div class="text-[11px] text-amber-700/80 dark:text-amber-400/80">Versi baru <span class="font-mono font-bold">{{ $newFirmwareVersion }}</span> siap diinstal.</div>
            </div>
        </div>

        <x-filament::button
            color="warning"
            size="xs"
            icon="heroicon-m-arrow-path"
            wire:click="updateFirmware"
            wire:confirm="Yakin update firmware ke {{ $newFirmwareVersion }}? Mesin akan restart."
            wire:loading.attr="disabled"
            class="w-full sm:w-auto font-medium shadow-2xs">
            Update Sekarang
        </x-filament::button>
    </div>
    @endif
</div>