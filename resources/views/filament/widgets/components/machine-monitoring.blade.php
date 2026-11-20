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

<div wire:click="navigateToRekap"
    wire:poll.2s="updateData"
    x-data="{ 
         showModal: false, 
         modalTitle: '', 
         modalMessage: '', 
         confirmAction: '' 
     }"
    class="p-4 sm:p-5 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl shadow-xs transition-all duration-200 relative overflow-hidden cursor-pointer hover:border-zinc-300 dark:hover:border-zinc-700 group mb-6">

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

    // Label ringkas untuk status firmware terbaru
    $teksStatusFirmware = $statusFirmware === 'terbaru' ? 'Newest version' : $labelFirmware;

    // Cek apakah riwayat update terakhir masih layak ditampilkan:
    // - Jika 'sukses', otomatis hilang setelah 10 menit
    // - Selain 'sukses' (gagal / dikembalikan), tetap dipertahankan
    $tampilkanHasilOta = false;
    $teksHasilOtaRingkas = $hasilOta;

    if ($hasilOta) {
    if ($kategoriOta === 'sukses') {
    $teksHasilOtaRingkas = 'Berhasil';
    $carbonOta = null;

    if (! empty($waktuOtaTimestamp)) {
    $carbonOta = is_numeric($waktuOtaTimestamp)
    ? \Carbon\Carbon::createFromTimestamp($waktuOtaTimestamp)
    : \Carbon\Carbon::parse($waktuOtaTimestamp);
    } elseif (! empty($waktuOta)) {
    try {
    $carbonOta = \Carbon\Carbon::parse($waktuOta);
    } catch (\Throwable $e) {
    // Fallback jika $waktuOta berupa string relatif Bahasa Indonesia (mis. "3 menit yang lalu")
    if (preg_match('/^(\d+)\s+(detik|menit)/i', trim($waktuOta), $m)) {
    $menit = strtolower($m[2]) === 'detik' ? 0 : (int) $m[1];
    $tampilkanHasilOta = $menit < 10;
        } elseif (preg_match('/baru\s+saja/i', $waktuOta)) {
        $tampilkanHasilOta=true;
        }
        }
        }

        if ($carbonOta) {
        $tampilkanHasilOta=$carbonOta->diffInMinutes(now()) < 10;
            }
            } else {
            $tampilkanHasilOta=true;
            }
            }

            $peringatanAktif=! empty($aktif)
            ? " Saat ini ada sanding berjalan ({$aktif['barang']}, palet {$aktif['no_palet']}); sebaiknya tunggu hingga selesai."
            : '' ;
            $pesanKonfirmasiFw="Pasang {$versiTersedia} pada {$machineNama}? Mesin akan restart dan berhenti menghitung sekitar 1 sampai 3 menit.{$peringatanAktif}" ;
            @endphp

            <!-- Bagian Firmware -->
            <div class="mb-4 p-3.5 rounded-xl bg-zinc-50/60 dark:bg-zinc-800/20 border border-zinc-200/70 dark:border-zinc-800 text-xs" wire:click.stop>
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="flex flex-wrap items-center gap-x-3 gap-y-1.5">
                        <span class="font-bold uppercase tracking-wider text-zinc-400 text-[10px]">Firmware</span>
                        <span class="font-mono font-bold px-2 py-0.5 rounded bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 border border-zinc-200/60 dark:border-zinc-700 text-[11px] shadow-2xs break-all">{{ $versiTerpasang ?? '-' }}</span>

                        @if($menungguVersi)
                        <span class="font-semibold text-amber-600 dark:text-amber-400">Update ke {{ $menungguVersi }} sedang berjalan</span>
                        @elseif($statusFirmware !== 'tersedia_update')
                        <span class="font-semibold {{ $warnaFw }} flex items-center gap-1.5">
                            {{ $teksStatusFirmware }}
                        </span>
                        @endif
                    </div>

                    @if($statusFirmware === 'tersedia_update' && ! $menungguVersi)
                    <div>
                        <button type="button"
                            @click.stop="showModal = true; modalTitle = 'Konfirmasi Update Firmware'; modalMessage = @js($pesanKonfirmasiFw); confirmAction = 'kirimUpdate()'"
                            @disabled(! $bisaUpdate)
                            wire:loading.attr="disabled"
                            wire:target="kirimUpdate"
                            class="inline-flex items-center gap-1.5 h-8 px-3 text-xs font-semibold rounded-lg bg-zinc-900 dark:bg-zinc-100 text-white dark:text-zinc-900 hover:bg-zinc-800 dark:hover:bg-zinc-200 shadow-2xs disabled:opacity-50 disabled:cursor-not-allowed">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path>
                            </svg>
                            Update ke {{ $versiTersedia }}
                        </button>
                    </div>
                    @endif
                </div>

                @if($alasanUpdate && ! $menungguVersi)
                <p class="mt-2 text-[11px] text-amber-600 dark:text-amber-400 font-medium">Update tidak bisa dilakukan: {{ $alasanUpdate }}</p>
                @endif

                {{-- Panel progress update (gaya kartu notifikasi shadcn): tahap, bar, stepper, dan hasil --}}
                @if(! empty($putaran['aktif']))
                @php
                $nada = $putaran['nada'];
                $selesaiPutaran = $putaran['selesai'];
                $langkahKini = $putaran['langkah'];

                $kotak = match ($nada) {
                'sukses' => 'border-emerald-200 dark:border-emerald-900/60 bg-emerald-50/60 dark:bg-emerald-950/30',
                'peringatan' => 'border-amber-200 dark:border-amber-900/60 bg-amber-50/60 dark:bg-amber-950/30',
                'galat' => 'border-red-200 dark:border-red-900/60 bg-red-50/60 dark:bg-red-950/30',
                default => 'border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900',
                };
                $warnaJudulPutaran = match ($nada) {
                'sukses' => 'text-emerald-700 dark:text-emerald-400',
                'peringatan' => 'text-amber-700 dark:text-amber-400',
                'galat' => 'text-red-700 dark:text-red-400',
                default => 'text-zinc-900 dark:text-zinc-100',
                };
                $warnaBar = match ($nada) {
                'sukses' => 'bg-emerald-500',
                'peringatan' => 'bg-amber-500',
                'galat' => 'bg-red-500',
                default => 'bg-zinc-900 dark:bg-zinc-100',
                };
                $warnaTitik = match ($nada) {
                'sukses' => 'bg-emerald-500 border-emerald-500 text-white',
                'peringatan' => 'bg-amber-500 border-amber-500 text-white',
                'galat' => 'bg-red-500 border-red-500 text-white',
                default => 'bg-zinc-900 dark:bg-zinc-100 border-zinc-900 dark:border-zinc-100 text-white dark:text-zinc-900',
                };
                @endphp

                <div class="mt-3 rounded-lg border p-3 {{ $kotak }}" role="status" aria-live="polite">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-xs font-semibold {{ $warnaJudulPutaran }}">{{ $putaran['judul'] }}</p>
                            <p class="mt-0.5 text-[11px] leading-relaxed text-zinc-500 dark:text-zinc-400">{{ $putaran['keterangan'] }}</p>
                        </div>
                        <span class="shrink-0 font-mono text-[11px] font-bold {{ $warnaJudulPutaran }}">{{ $putaran['persen'] }}%</span>
                    </div>

                    <!-- Bar kemajuan -->
                    <div class="mt-2.5 h-1.5 w-full overflow-hidden rounded-full bg-zinc-200/70 dark:bg-zinc-800"
                        role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $putaran['persen'] }}">
                        <div class="h-full rounded-full transition-all duration-500 {{ $warnaBar }} @if(! $selesaiPutaran) animate-pulse @endif"
                            style="width: {{ $putaran['persen'] }}%"></div>
                    </div>

                    <!-- Langkah -->
                    <ol class="mt-3 flex items-start justify-between gap-1">
                        @foreach(\App\Support\Ota\OtaFase::LANGKAH as $i => $namaLangkah)
                        @php
                        $lalu = $i < $langkahKini && ($nada !=='galat' || $i===0);
                            $kini=$i===$langkahKini;
                            $akhirDiSini=$kini && $selesaiPutaran;
                            @endphp
                            <li class="flex flex-1 flex-col items-center gap-1 text-center">
                            @if($lalu)
                            <span class="flex w-5 h-5 items-center justify-center rounded-full border bg-emerald-500 border-emerald-500 text-white">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </span>
                            @elseif($akhirDiSini)
                            <span class="flex w-5 h-5 items-center justify-center rounded-full border {{ $warnaTitik }}">
                                @if($nada === 'sukses')
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                                </svg>
                                @elseif($nada === 'galat')
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                                @else
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 9v2m0 4h.01"></path>
                                </svg>
                                @endif
                            </span>
                            @elseif($kini)
                            <span class="flex w-5 h-5 items-center justify-center rounded-full border {{ $warnaTitik }}">
                                <span class="w-1.5 h-1.5 rounded-full bg-white dark:bg-zinc-900 animate-pulse"></span>
                            </span>
                            @else
                            <span class="flex w-5 h-5 items-center justify-center rounded-full border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900"></span>
                            @endif
                            <span class="text-[10px] leading-tight {{ ($lalu || $kini) ? 'font-semibold text-zinc-700 dark:text-zinc-300' : 'text-zinc-400 dark:text-zinc-500' }}">{{ $namaLangkah }}</span>
                            </li>
                            @endforeach
                    </ol>

                    <div class="mt-3 flex items-center justify-between gap-3">
                        <span class="min-w-0 truncate text-[10px] text-zinc-400 dark:text-zinc-500">Tujuan {{ $putaran['versi'] }} · dikirim {{ $putaran['dikirim'] }}</span>
                        @if($selesaiPutaran)
                        <button type="button" wire:click="tutupPutaran"
                            class="shrink-0 inline-flex items-center h-7 px-2.5 text-[11px] font-medium rounded-md border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700 transition-colors">
                            Tutup
                        </button>
                        @endif
                    </div>
                </div>
                @elseif($tampilkanHasilOta)
                <p class="mt-2 text-[11px] {{ $warnaHasil }} font-medium flex items-center gap-1">
                    <span>Update terakhir: {{ $teksHasilOtaRingkas }}@if($waktuOta) · {{ str_replace(' yang lalu', ' lalu', $waktuOta) }}@endif</span>
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

            <!-- Tombol Reset -->
            <div class="mb-4 grid grid-cols-1 gap-2 sm:flex sm:flex-wrap sm:items-center sm:justify-end" wire:click.stop>
                <button type="button"
                    @click.stop="showModal = true; modalTitle = 'Konfirmasi Reset'; modalMessage = 'Yakin ingin mereset {{ $labelSensor1 }} ke 0?'; confirmAction = 'resetCounter(\'sensor1\')'"
                    @disabled(! $isOnline)
                    class="inline-flex items-center justify-center h-8 px-3 text-xs font-medium rounded-lg shadow-2xs transition-colors bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 border border-zinc-200 dark:border-zinc-700 hover:bg-zinc-100 dark:hover:bg-zinc-700 disabled:opacity-50 disabled:cursor-not-allowed">
                    Reset {{ $labelSensor1 }}
                </button>

                <button type="button"
                    @click.stop="showModal = true; modalTitle = 'Konfirmasi Reset'; modalMessage = 'Yakin ingin mereset {{ $labelSensor2 }} ke 0?'; confirmAction = 'resetCounter(\'sensor2\')'"
                    @disabled(! $isOnline)
                    class="inline-flex items-center justify-center h-8 px-3 text-xs font-medium rounded-lg shadow-2xs transition-colors bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 border border-zinc-200 dark:border-zinc-700 hover:bg-zinc-100 dark:hover:bg-zinc-700 disabled:opacity-50 disabled:cursor-not-allowed">
                    Reset {{ $labelSensor2 }}
                </button>

                <button type="button"
                    @click.stop="showModal = true; modalTitle = 'Konfirmasi Reset Semua'; modalMessage = 'Yakin ingin mereset KEDUANYA ke 0?'; confirmAction = 'resetCounter(\'all\')'"
                    @disabled(! $isOnline)
                    class="inline-flex items-center justify-center gap-1.5 h-8 px-3 text-xs font-medium rounded-lg shadow-2xs transition-colors bg-zinc-950 dark:bg-zinc-50 text-white dark:text-zinc-900 hover:bg-zinc-800 dark:hover:bg-zinc-200 disabled:opacity-50 disabled:cursor-not-allowed">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    Reset Semua
                </button>
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

            <!-- STRUKTUR POP-UP MODAL -->
            <div x-show="showModal"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                @click.stop
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-zinc-950/60 backdrop-blur-xs"
                style="display: none;">

                <div @click.away="showModal = false" class="w-full max-w-md p-6 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl shadow-2xl">
                    <h3 class="text-base font-bold text-zinc-900 dark:text-zinc-100 mb-2" x-text="modalTitle"></h3>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-6 leading-relaxed" x-text="modalMessage"></p>

                    <div class="flex items-center justify-end gap-2.5">
                        <button type="button" @click="showModal = false" class="px-3.5 py-2 text-xs font-medium rounded-lg bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-200 dark:hover:bg-zinc-700 transition-colors">
                            Batal
                        </button>
                        <button type="button"
                            @click="showModal = false; eval('$wire.' + confirmAction)"
                            class="px-4 py-2 text-xs font-semibold rounded-lg bg-zinc-950 dark:bg-zinc-50 text-white dark:text-zinc-900 hover:bg-zinc-800 dark:hover:bg-zinc-200 transition-colors">
                            Ya, Lanjutkan
                        </button>
                    </div>
                </div>
            </div>
</div>