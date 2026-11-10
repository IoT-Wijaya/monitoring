@php
// Semua variabel di bawah adalah properti public dari widget DeviceHealthOverview.
$isOnline = ($statusMesin ?? 'OFFLINE') === 'ONLINE';
$namaTampil = $machineNama ?? 'Perangkat HMI';
$adaPembaruan = ($newFirmwareAvailable ?? false) === true;
$targetVersion = $newFirmwareVersion ?? '-';

$hijau = 'text-emerald-600 dark:text-emerald-400';
$merah = 'text-red-500 dark:text-red-400';
$kuning = 'text-amber-600 dark:text-amber-400';
$abu = 'text-zinc-400 dark:text-zinc-500';

// Hijau/merah hanya bermakna kalau HMI online. Saat offline, data diag adalah
// laporan TERAKHIR (basi), jadi ditampilkan abu-abu "Tidak diketahui".
$warna = fn (bool $baik) => $isOnline ? ($baik ? $hijau : $merah) : $abu;

$kodeHttp = $httpCode ?? '-';

// Kode 0 dari firmware = belum pernah ada request HTTP (bukan gagal).
$belumAdaHttp = ($httpCode ?? 0) === 0;

// Tiga lampu indikator, masing-masing dibaca dari flag-nya sendiri.
$barisStatus = [
[
'label' => 'Status Jaringan (LAN):',
'kelas' => $warna($lanOk),
'teks' => $isOnline ? ($lanOk ? 'Terhubung (Link OK)' : 'Terputus (No Link)') : 'Tidak diketahui',
],
[
'label' => 'Status MQTT Broker:',
'kelas' => $warna($mqttOk),
'teks' => $isOnline ? ($mqttOk ? 'Connected (Broker OK)' : 'Disconnected') : 'Tidak diketahui',
],
[
'label' => 'Status HTTP ke ERP:',
'kelas' => $belumAdaHttp ? $abu : $warna($httpOk),
'teks' => ! $isOnline
? 'Tidak diketahui'
: ($belumAdaHttp
? 'Belum ada request'
: ($httpOk ? "Responsif (kode {$kodeHttp})" : "Gagal (kode {$kodeHttp})")),
],
];

// Mode sensor: ON = baik, '-' = belum ada data, selain itu = perlu perhatian.
$kelasSensor = ! $isOnline || $sensorMode === '-'
? $abu
: ($sensorMode === 'ON' ? $hijau : $kuning);

$barisInfo = [
['label' => 'Alamat IP:', 'nilai' => $ipAddress, 'kelas' => 'text-zinc-800 dark:text-zinc-200'],
['label' => 'MAC Address:', 'nilai' => $macAddress, 'kelas' => 'text-zinc-800 dark:text-zinc-200'],
['label' => 'Mode Sensor:', 'nilai' => $sensorMode, 'kelas' => $kelasSensor],
['label' => 'HTTP sukses terakhir:', 'nilai' => $httpOkAgo, 'kelas' => 'text-zinc-800 dark:text-zinc-200'],
['label' => 'Versi Firmware:', 'nilai' => $firmwareVersion, 'kelas' => 'text-zinc-800 dark:text-zinc-200 font-bold'],
];

$kelasKartu = 'bg-zinc-50/70 dark:bg-zinc-800/30 p-3 rounded-md border border-zinc-200/70 dark:border-zinc-800 text-center';
$kelasLabelKartu = 'text-[10px] font-medium text-zinc-400 dark:text-zinc-500 uppercase tracking-wider block mb-1';
@endphp

<div class="p-4 sm:p-5 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-lg shadow-xs space-y-4">
    <!-- Header -->
    <div class="flex items-center justify-between pb-3 border-b border-zinc-100 dark:border-zinc-800/80">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-md flex items-center justify-center bg-zinc-100 dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 border border-zinc-200/60 dark:border-zinc-700/60 shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                </svg>
            </div>
            <div>
                <span class="text-[11px] font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider block">HMI Health Management</span>
                <span class="text-sm sm:text-base font-bold text-zinc-900 dark:text-zinc-100 tracking-tight block">{{ $namaTampil }}</span>
            </div>
        </div>

        <div>
            @if($isOnline)
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-md text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200/80 dark:bg-emerald-500/10 dark:text-emerald-400 dark:border-emerald-500/20">
                <span class="w-2 h-2 rounded-full bg-emerald-500"></span> ONLINE
            </span>
            @else
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-md text-xs font-bold bg-red-50 text-red-700 border border-red-200/80 dark:bg-red-500/10 dark:text-red-400 dark:border-red-500/20">
                <span class="w-2 h-2 rounded-full bg-red-500"></span> OFFLINE
            </span>
            @endif
        </div>
    </div>

    <!-- Uptime & RAM -->
    <div class="grid grid-cols-2 gap-3">
        <div class="{{ $kelasKartu }}">
            <span class="{{ $kelasLabelKartu }}">Uptime</span>
            <span class="text-xs leading-relaxed tracking-wide font-bold text-zinc-800 dark:text-zinc-200">{{ $uptimeString }}</span>
        </div>
        <div class="{{ $kelasKartu }}">
            <span class="{{ $kelasLabelKartu }}">Free Heap (RAM)</span>
            <span class="text-xs leading-relaxed tracking-wide font-bold text-zinc-800 dark:text-zinc-200">{{ $freeHeapKb }}</span>
        </div>
    </div>

    <!-- Status koneksi & Info -->
    <div class="divide-y divide-zinc-100 dark:divide-zinc-800/60 text-xs font-medium">
        @foreach($barisStatus as $b)
        <div class="py-2.5 flex items-center justify-between">
            <span class="text-zinc-500 dark:text-zinc-400">{{ $b['label'] }}</span>
            <span class="{{ $b['kelas'] }} leading-relaxed tracking-wide">{{ $b['teks'] }}</span>
        </div>
        @endforeach

        @foreach($barisInfo as $b)
        <div class="py-2.5 flex items-center justify-between">
            <span class="text-zinc-500 dark:text-zinc-400">{{ $b['label'] }}</span>
            <span class="{{ $b['kelas'] }} leading-relaxed tracking-wide">{{ $b['nilai'] }}</span>
        </div>
        @endforeach
    </div>

    <!-- Panel OTA & Firmware Update -->
    @if($otaBerjalan)
    <div class="p-3.5 rounded-md bg-amber-500/10 border border-amber-500/20 space-y-2.5">
        <div class="flex items-center justify-between gap-3">
            <div class="flex items-center gap-2 text-xs font-bold text-amber-800 dark:text-amber-300">
                <x-filament::loading-indicator class="w-4 h-4" />
                Memperbarui firmware ke <span class="leading-relaxed tracking-wide">{{ $otaTarget }}</span>
            </div>
            <span class="text-[11px] leading-relaxed tracking-wide text-amber-700/80 dark:text-amber-400/80">
                @if($otaPersen !== null){{ $otaPersen }}% · @endif{{ gmdate('i:s', $otaDetik) }}
            </span>
        </div>

        <div class="h-1.5 w-full rounded-full bg-amber-500/20 overflow-hidden">
            @if($otaPersen !== null)
            <div class="h-full rounded-full bg-amber-500 transition-all duration-500" style="width: {{ $otaPersen }}%"></div>
            @else
            <div class="h-full w-full rounded-full bg-amber-500 animate-pulse"></div>
            @endif
        </div>

        <div class="text-[11px] text-amber-700/80 dark:text-amber-400/80">
            {{ $otaPesan ?: 'HMI sedang mengunduh dan memasang firmware, lalu restart. Jangan matikan perangkat.' }}
        </div>
    </div>
    @elseif($isOnline && $adaPembaruan)
    <div class="p-3.5 rounded-md bg-amber-500/10 border border-amber-500/20 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <div class="text-amber-600 dark:text-amber-400 shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                </svg>
            </div>
            <div>
                <div class="text-xs font-bold text-amber-800 dark:text-amber-300">Pembaruan Firmware Tersedia!</div>
                <div class="text-[11px] text-amber-700/80 dark:text-amber-400/80">Versi baru <span class="leading-relaxed tracking-wide font-bold">{{ $targetVersion }}</span> siap diinstal.</div>
            </div>
        </div>

        {{ $this->updateFirmwareAction }}
    </div>
    @endif
</div>