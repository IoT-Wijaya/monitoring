<x-filament-widgets::widget>
    <x-filament::section wire:poll.2s="updateData" class="!p-0">

        <div class="p-5 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-3xl shadow-sm transition-all duration-300 relative overflow-hidden">

            <div class="flex items-start justify-between">

                <!-- WiFi Icon Badge with Dynamic Signal Colors -->
                <div class="w-12 h-12 rounded-full flex items-center justify-center transition-colors duration-300
                    {{ $kecepatanSinyal >= 50 ? 'bg-emerald-500/10 text-emerald-500 border border-emerald-500/20' : ($kecepatanSinyal >= 20 ? 'bg-amber-500/10 text-amber-500 border border-amber-500/20' : 'bg-red-500/10 text-red-500 border border-red-500/20') }}">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.117 10.325a9.003 9.003 0 017.766 0M5.293 7.502a13.002 13.002 0 0113.414 0M12 17.512h.01M10.12 14.25a4.5 4.5 0 013.76 0"></path>
                    </svg>
                </div>

                <!-- WiFi Speed Metric -->
                <div class="text-right">
                    <span class="text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider block">Sinyal Wi-Fi</span>
                    <div class="flex items-baseline justify-end gap-1 mt-0.5">
                        <span class="text-lg font-extrabold font-mono tracking-tight transition-colors duration-300
                            {{ $kecepatanSinyal >= 50 ? 'text-emerald-600 dark:text-emerald-400' : ($kecepatanSinyal >= 20 ? 'text-amber-500 dark:text-amber-400' : 'text-red-500 dark:text-red-400') }}">
                            {{ $kecepatanSinyal }}
                        </span>
                        <span class="text-xs font-bold text-gray-400">Mbps</span>
                    </div>
                </div>
            </div>

            <div class="mt-4 mb-5">
                <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-widest block">Total Hasil</span>
                <div class="flex items-baseline gap-2 mt-0.5">
                    <span class="text-4xl sm:text-5xl font-black font-mono text-gray-900 dark:text-white tracking-tight">
                        {{ number_format($totalSanding) }}
                    </span>
                    <span class="text-sm font-bold text-gray-400 uppercase">pcs</span>
                </div>
                <h2 class="text-lg font-bold text-gray-800 dark:text-gray-200 tracking-tight mt-1">Modal Sanding</h2>
            </div>

            <div class="pt-3 border-t border-gray-100 dark:border-gray-800/80 flex items-center justify-between">

                <!-- Device Connection Status -->
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

                <!-- Subtext Info -->
                <span class="text-[11px] font-medium text-gray-400">
                    {{ $statusMesin === 'ONLINE' ? 'ESP32 Connected' : 'ESP32 Disconnected' }}
                </span>
            </div>

        </div>
    </x-filament::section>
</x-filament-widgets::widget>