@php
$isOnline = $statusMesin === 'ONLINE';
$kondisi = $kondisi ?? ($isOnline ? 'ONLINE' : 'TIDAK_ADA_KABAR');
$diag = $diag ?? null;
$terakhirTerlihat = $terakhirTerlihat ?? null;

$net = match ($kondisi) {
'ONLINE' => ['lan' => 'OK', 'mqtt' => 'OK', 'warna' => 'text-emerald-600 dark:text-emerald-400'],
'PEMANTAU_MATI' => ['lan' => '?', 'mqtt' => '?', 'warna' => 'text-amber-600 dark:text-amber-400'],
default => ['lan' => '?', 'mqtt' => '?', 'warna' => 'text-red-500 dark:text-red-400'],
};

$labelKondisi = match ($kondisi) {
'ONLINE' => 'ONLINE',
'TERPUTUS_MENDADAK' => 'TERPUTUS MENDADAK',
'PEMANTAU_MATI' => 'PEMANTAU DATA BERHENTI',
default => 'TIDAK ADA KABAR',
};

$waktuTerlihat = $terakhirTerlihat ? \Carbon\Carbon::createFromTimestamp($terakhirTerlihat) : null;
@endphp

<div wire:click="navigateToRekap" class="p-4 sm:p-5 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl shadow-xs transition-all duration-200 relative overflow-hidden cursor-pointer hover:border-zinc-300 dark:hover:border-zinc-700 group mb-6">

    <!-- Header Mesin & Jaringan -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3.5 border-b border-zinc-100 dark:border-zinc-800/80 mb-4">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg flex items-center justify-center bg-zinc-100 dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 border border-zinc-200/60 dark:border-zinc-700/60 shrink-0 shadow-2xs">
                <svg class="w-5 h-5 text-zinc-700 dark:text-zinc-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
            </div>
            <div>
                <span class="text-[10px] font-bold text-zinc-400 uppercase tracking-widest block">Monitoring Produksi</span>
                <span class="text-sm sm:text-base font-bold text-zinc-900 dark:text-zinc-100 tracking-tight block">{{ $machineNama }}</span>
            </div>
        </div>

        <div class="w-full sm:w-auto flex items-center justify-between sm:justify-start gap-2.5 bg-zinc-50 dark:bg-zinc-800/40 px-3 py-1.5 rounded-lg border border-zinc-200/60 dark:border-zinc-800">
            <span class="text-[10px] font-bold text-zinc-400 uppercase tracking-wider">Jaringan</span>
            <div class="flex items-center gap-2 font-mono text-xs font-bold {{ $net['warna'] }}">
                <span class="px-1.5 py-0.5 rounded bg-white dark:bg-zinc-900 shadow-2xs">LAN: {{ $net['lan'] }}</span>
                <span class="px-1.5 py-0.5 rounded bg-white dark:bg-zinc-900 shadow-2xs">MQTT: {{ $net['mqtt'] }}</span>
            </div>
        </div>
    </div>

    {{-- Bagian Proses (Sanding Berjalan) disembunyikan sesuai permintaan --}}

    @php
    $warnaFw = match ($statusFirmware) {
    'terbaru' => 'text-emerald-600 dark:text-emerald-400',
    'tersedia_update' => 'text-amber-600 dark:text-amber-400',
    default => 'text-zinc-500 dark:text-zinc-400',
    };

    $warnaHasil = match ($kategoriOta) {
    'sukses' => 'text-emerald-600 dark:text-emerald-400',
    'dikembalikan' => 'text-amber-600 dark:text-amber-400',
    'gagal' => 'text-red-600 dark:text-red-400',
    default => 'text-zinc-500 dark:text-zinc-400',
    };

    $peringatanAktif = ! empty($aktif)
    ? " Saat ini ada sanding berjalan ({$aktif['barang']}, palet {$aktif['no_palet']}); sebaiknya tunggu hingga selesai."
    : '';
    $pesanKonfirmasi = "Pasang {$versiTersedia} pada {$machineNama}? Mesin akan restart dan berhenti menghitung sekitar 1 sampai 3 menit.{$peringatanAktif}";
    @endphp

    <!-- Bagian Firmware yang Diperbarui (Lebih Cantik & Bersih) -->
    <div class="mb-4 p-3.5 rounded-xl bg-zinc-50/60 dark:bg-zinc-800/20 border border-zinc-200/70 dark:border-zinc-800 text-xs" wire:click.stop>
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap items-center gap-x-3 gap-y-1.5">
                <span class="font-bold uppercase tracking-wider text-zinc-400 text-[10px]">Firmware</span>
                <span class="font-mono font-bold px-2 py-0.5 rounded bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 border border-zinc-200/60 dark:border-zinc-700 text-[11px] shadow-2xs">{{ $versiTerpasang ?? '-' }}</span>

                @if($menungguVersi)
                <span class="font-semibold text-amber-600 dark:text-amber-400 flex items-center gap-1.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-ping"></span> Menunggu mesin memasang {{ $menungguVersi }}…
                </span>
                @else
                <span class="font-semibold {{ $warnaFw }} flex items-center gap-1.5">
                    @if($statusFirmware === 'tersedia_update')
                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                    @endif
                    {{ $labelFirmware }}@if($statusFirmware === 'tersedia_update' && $versiTersedia) ({{ $versiTersedia }})@endif
                </span>
                @endif
            </div>

            @if($statusFirmware === 'tersedia_update')
            <div>
                <x-filament::button
                    wire:click="kirimUpdate"
                    wire:confirm="{{ $pesanKonfirmasi }}"
                    wire:loading.attr="disabled"
                    wire:target="kirimUpdate"
                    size="xs"
                    icon="heroicon-m-arrow-up-tray"
                    :disabled="! $bisaUpdate"
                    class="h-8 px-3 text-xs font-semibold rounded-lg bg-zinc-900 dark:bg-zinc-100 text-white dark:text-zinc-900 hover:bg-zinc-800 dark:hover:bg-zinc-200 shadow-2xs">
                    Update ke {{ $versiTersedia }}
                </x-filament::button>
            </div>
            @endif
        </div>

        @if($statusFirmware === 'tersedia_update' && ! $bisaUpdate && ! $menungguVersi)
        <p class="mt-2 text-[11px] text-zinc-400 dark:text-zinc-500 font-medium">Tombol nonaktif: mesin sedang offline.</p>
        @endif

        @if($hasilOta)
        <p class="mt-2 text-[11px] {{ $warnaHasil }} font-medium flex items-center gap-1">
            <span>Update terakhir: {{ $hasilOta }}@if($waktuOta) · {{ $waktuOta }}@endif</span>
        </p>
        @endif
    </div>

    <!-- Sensor Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5 mb-4 items-stretch">
        <div class="bg-zinc-50/70 dark:bg-zinc-800/30 p-3.5 rounded-xl border border-zinc-200/70 dark:border-zinc-800">
            <div class="flex items-center justify-between mb-1.5">
                <span class="text-[10px] font-bold text-zinc-400 uppercase tracking-widest">Sensor 1</span>
                <span class="text-[11px] font-medium text-zinc-500 dark:text-zinc-400">{{ $labelSensor1 }}</span>
            </div>
            <div class="flex items-baseline gap-1.5 my-1">
                <span class="text-2xl sm:text-3xl font-bold font-mono text-zinc-900 dark:text-zinc-100 tracking-tight">{{ number_format($totalSensor1) }}</span>
                <span class="text-[10px] font-bold text-zinc-400 uppercase">{{ $satuan }}</span>
            </div>
            <span class="text-[11px] text-zinc-500 dark:text-zinc-400 block mt-1 font-mono">Terakhir: {{ $terakhirSensor1 }}</span>
        </div>

        <div class="bg-zinc-50/70 dark:bg-zinc-800/30 p-3.5 rounded-xl border border-zinc-200/70 dark:border-zinc-800">
            <div class="flex items-center justify-between mb-1.5">
                <span class="text-[10px] font-bold text-zinc-400 uppercase tracking-widest">Sensor 2</span>
                <span class="text-[11px] font-medium text-zinc-500 dark:text-zinc-400">{{ $labelSensor2 }}</span>
            </div>
            <div class="flex items-baseline gap-1.5 my-1">
                <span class="text-2xl sm:text-3xl font-bold font-mono text-zinc-900 dark:text-zinc-100 tracking-tight">{{ number_format($totalSensor2) }}</span>
                <span class="text-[10px] font-bold text-zinc-400 uppercase">{{ $satuan }}</span>
            </div>
            <span class="text-[11px] text-zinc-500 dark:text-zinc-400 block mt-1 font-mono">Terakhir: {{ $terakhirSensor2 }}</span>
        </div>
    </div>

    <!-- Tombol Reset (Tanpa color="gray" agar warna background & teks solid di light/dark mode) -->
    <div class="mb-4 grid grid-cols-1 gap-2 sm:flex sm:flex-wrap sm:items-center sm:justify-end" wire:click.stop>
        <x-filament::button wire:click="resetCounter('sensor1')" wire:confirm="Yakin ingin mereset {{ $labelSensor1 }} ke 0?" size="xs" class="w-full sm:w-auto justify-center h-8 px-3 text-xs font-medium rounded-lg shadow-2xs transition-colors bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 border border-zinc-200 dark:border-zinc-700 hover:bg-zinc-100 dark:hover:bg-zinc-700" :disabled="! $isOnline">
            Reset {{ $labelSensor1 }}
        </x-filament::button>
        <x-filament::button wire:click="resetCounter('sensor2')" wire:confirm="Yakin ingin mereset {{ $labelSensor2 }} ke 0?" size="xs" class="w-full sm:w-auto justify-center h-8 px-3 text-xs font-medium rounded-lg shadow-2xs transition-colors bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 border border-zinc-200 dark:border-zinc-700 hover:bg-zinc-100 dark:hover:bg-zinc-700" :disabled="! $isOnline">
            Reset {{ $labelSensor2 }}
        </x-filament::button>
        <x-filament::button wire:click="resetCounter('all')" wire:confirm="Yakin ingin mereset KEDUANYA ke 0?" size="xs" icon="heroicon-m-arrow-path" class="w-full sm:w-auto justify-center h-8 px-3 text-xs font-medium rounded-lg shadow-2xs transition-colors bg-zinc-950 dark:bg-zinc-50 text-white dark:text-zinc-900 hover:bg-zinc-800 dark:hover:bg-zinc-200" :disabled="! $isOnline">
            Reset Semua
        </x-filament::button>
    </div>

    <!-- Footer Status -->
    <div class="pt-3.5 border-t border-zinc-100 dark:border-zinc-800/80 flex items-center justify-between">
        <div class="flex items-center gap-2">
            @if($kondisi === 'ONLINE')
            <span class="relative flex h-2.5 w-2.5">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-500"></span>
            </span>
            <span class="text-xs font-bold text-emerald-600 dark:text-emerald-400">{{ $labelKondisi }}</span>
            @else
            <span class="inline-flex rounded-full h-2.5 w-2.5 bg-red-500"></span>
            <span class="text-xs font-bold text-red-600 dark:text-red-400">{{ $labelKondisi }}</span>
            @endif
        </div>
        <div class="flex items-center gap-1.5 text-xs font-semibold text-zinc-700 dark:text-zinc-300 group-hover:text-zinc-900 dark:group-hover:text-white transition-colors">
            <span>Lihat Rekapitulasi</span>
            <svg class="w-4 h-4 transform group-hover:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
        </div>
    </div>
</div>