<div>
    @assets
    {{-- Flatpickr CSS & JS --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    @endassets

    <!-- Header Page -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <x-filament::button color="success" icon="heroicon-m-arrow-down-tray" wire:click="exportRekap" class="rounded-[4px] w-full sm:w-auto">
                Export Data
            </x-filament::button>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="p-4 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-[4px] shadow-sm flex flex-col lg:flex-row lg:items-center justify-between gap-4 mb-6">
        <div class="flex flex-col sm:flex-row sm:flex-wrap items-stretch sm:items-center gap-3 flex-1">

            <!-- Date Range Input -->
            <div class="relative w-full sm:w-auto sm:min-w-[240px]" x-data x-init="
                flatpickr($refs.dateRange, {
                    mode: 'range',
                    dateFormat: 'Y-m-d',
                    altInput: true,
                    altFormat: 'd-m-Y',
                    conjunction: ' to ',
                    defaultDate: ['{{ $tanggalDari }}', '{{ $tanggalSampai }}'],
                    onChange: function(selectedDates, dateStr, instance) {
                        if(selectedDates.length === 2) {
                            @this.set('tanggalDari', instance.formatDate(selectedDates[0], 'Y-m-d'));
                            @this.set('tanggalSampai', instance.formatDate(selectedDates[1], 'Y-m-d'));
                        }
                    }
                })
            ">
                <input x-ref="dateRange" type="text" placeholder="Pilih Rentang Tanggal..." class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-300 dark:border-gray-700 text-xs text-gray-900 dark:text-gray-100 rounded-[4px] px-3 py-2.5 outline-none focus:ring-2 focus:ring-primary-500">
            </div>

            <!-- Searchable Dropdown Mesin -->
            <div x-data="{ 
                open: false, 
                search: '', 
                selected: @entangle('machineId').live,
                items: {{ $daftarMesin->map(fn($m) => ['id' => $m->id, 'label' => $m->nama])->toJson() }},
                get filteredItems() {
                    if (this.search.trim() === '') return this.items;
                    return this.items.filter(i => i.label.toLowerCase().includes(this.search.toLowerCase()));
                }
            }" class="relative w-full sm:w-auto sm:min-w-[260px]">

                <button @click="open = !open" type="button" class="w-full text-xs bg-gray-50 dark:bg-gray-800 border border-gray-300 dark:border-gray-700 text-gray-900 dark:text-gray-100 rounded-[4px] px-3 py-2.5 flex justify-between items-center outline-none focus:ring-2 focus:ring-primary-500 cursor-pointer">
                    <span class="font-bold truncate" x-text="selected ? items.find(i => i.id == selected)?.label : 'Semua Mesin'"></span>
                    <svg class="w-3 h-3 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>

                <div x-show="open" x-transition @click.away="open = false" class="absolute z-50 mt-1 w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-xl rounded-[4px] overflow-hidden" style="display: none;">
                    <div class="p-2 border-b border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 flex items-center relative">
                        <input x-model="search" type="text" placeholder="Cari nama mesin..." class="w-full bg-white dark:bg-gray-800 text-[11px] border border-gray-200 dark:border-gray-700 rounded-[4px] p-1.5 pr-7 outline-none text-gray-900 dark:text-gray-100">
                        <button x-show="search.length > 0" @click="search = ''" class="absolute right-4 text-gray-400 hover:text-red-500 cursor-pointer">×</button>
                    </div>
                    <div class="max-h-60 overflow-y-auto custom-scrollbar font-sans text-xs">
                        <div @click="selected = ''; open = false; search = ''" class="px-3 py-2 text-[10px] font-black text-gray-400 uppercase hover:bg-gray-100 dark:hover:bg-gray-700 cursor-pointer">Semua Mesin</div>
                        <template x-for="item in filteredItems" :key="item.id">
                            <div @click="selected = item.id; open = false; search = ''" class="px-3 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 cursor-pointer border-b border-gray-50 dark:border-gray-800 last:border-0 transition-colors">
                                <span class="font-bold text-gray-800 dark:text-gray-200" x-text="item.label"></span>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Shift Dropdown & Reset Button Row on Small Screens -->
            <div class="grid grid-cols-2 gap-2 sm:flex sm:items-center">
                <div class="w-full sm:w-auto">
                    <select wire:model.live="shift" class="w-full sm:w-auto bg-gray-50 dark:bg-gray-800 border border-gray-300 dark:border-gray-700 text-xs text-gray-900 dark:text-gray-100 rounded-[4px] px-3 py-2.5 outline-none focus:ring-2 focus:ring-primary-500">
                        <option value="">Semua Shift</option>
                        <option value="Shift Pagi">Shift Pagi</option>
                        <option value="Shift Malam">Shift Malam</option>
                    </select>
                </div>
                <div class="w-full sm:w-auto">
                    <x-filament::button color="gray" wire:click="resetFilter" size="sm" icon="heroicon-m-arrow-path" class="rounded-[4px] w-full sm:w-auto justify-center">
                        Reset
                    </x-filament::button>
                </div>
            </div>
        </div>

        <!-- Total Counter Badge -->
        <div class="text-xs text-gray-500 dark:text-gray-400 font-medium px-3 py-2 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-[4px] text-center lg:text-left">
            Total: <span class="font-bold text-warning-600 dark:text-warning-400">{{ $recaps->total() }}</span> Data Rekap
        </div>
    </div>

    <!-- RESPONSIVE VIEW: CARD LIST UNTUK MOBILE & TABEL UNTUK DESKTOP -->
    <div>
        @forelse($recaps as $index => $row)
        @php
        $selisih = $row->sensor2_keluar - $row->sensor1_masuk;
        $shiftBadgeColor = $row->shift === 'Shift Malam'
        ? 'bg-purple-500/10 text-purple-600 dark:text-purple-400 border-purple-500/20'
        : 'bg-warning-500/10 text-warning-600 dark:text-warning-400 border-warning-500/20';
        @endphp

        <!-- TAMPILAN MOBILE (Card View - Hanya tampil di layar kecil md:hidden) -->
        <div class="block md:hidden p-3.5 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-[6px] shadow-sm space-y-2.5 mb-3">
            <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-800 pb-2">
                <div class="flex items-center gap-2">
                    <span class="text-[10px] font-mono text-gray-400 font-bold">#{{ $recaps->firstItem() + $index }}</span>
                    <span class="text-xs font-bold text-gray-800 dark:text-gray-200">{{ $row->tanggal }}</span>
                </div>
                <span class="px-2 py-0.5 rounded text-[10px] font-semibold border {{ $shiftBadgeColor }}">{{ $row->shift }}</span>
            </div>
            <div>
                <div class="text-xs font-black text-gray-900 dark:text-white truncate">{{ $row->machine->nama ?? '-' }}</div>
            </div>
            <div class="grid grid-cols-3 gap-1.5 pt-1.5 border-t border-gray-100 dark:border-gray-800 text-center">
                <div class="bg-gray-50 dark:bg-gray-800/60 p-1.5 rounded">
                    <div class="text-[9px] text-gray-400 uppercase font-semibold">Masuk</div>
                    <div class="text-[12px] font-bold text-gray-700 dark:text-gray-200">{{ number_format($row->sensor1_masuk) }}</div>
                </div>
                <div class="bg-gray-50 dark:bg-gray-800/60 p-1.5 rounded">
                    <div class="text-[9px] text-gray-400 uppercase font-semibold">Keluar</div>
                    <div class="text-[12px] font-bold text-emerald-600 dark:text-emerald-400">{{ number_format($row->sensor2_keluar) }}</div>
                </div>
                <div class="bg-gray-50 dark:bg-gray-800/60 p-1.5 rounded">
                    <div class="text-[9px] text-gray-400 uppercase font-semibold">Selisih</div>
                    <div class="text-[12px] font-bold">
                        @if($selisih < 0)
                            <span class="text-danger-600 dark:text-danger-400">{{ number_format($selisih) }}</span>
                            @elseif($selisih > 0)
                            <span class="text-emerald-600 dark:text-emerald-400">+{{ number_format($selisih) }}</span>
                            @else
                            <span class="text-gray-500">0</span>
                            @endif
                    </div>
                </div>
            </div>
        </div>
        @empty
        <!-- Kosong untuk Mobile & Desktop -->
        <div class="block md:hidden p-8 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-[6px] text-center text-xs text-gray-500">
            Tidak ada data rekapitulasi yang sesuai dengan filter.
        </div>
        @endforelse

        <!-- TAMPILAN DESKTOP (Tabel Klasik - Hanya tampil di ukuran md ke atas) -->
        <div class="hidden md:block bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-[4px] overflow-hidden shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse min-w-[700px]">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-gray-800/60 border-b border-gray-200 dark:border-gray-800 text-[11px] uppercase tracking-wider text-gray-500 dark:text-gray-400 font-semibold">
                            <th class="p-3.5 w-12 text-center">#</th>
                            <th class="p-3.5">Tanggal</th>
                            <th class="p-3.5">Nama Mesin</th>
                            <th class="p-3.5">Status Shift</th>
                            <th class="p-3.5 text-right">Masuk</th>
                            <th class="p-3.5 text-right">Keluar</th>
                            <th class="p-3.5 text-right">Selisih</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-800 text-xs">
                        @forelse($recaps as $index => $row)
                        @php
                        $selisih = $row->sensor2_keluar - $row->sensor1_masuk;
                        $shiftBadgeColor = $row->shift === 'Shift Malam'
                        ? 'bg-purple-500/10 text-purple-600 dark:text-purple-400 border-purple-500/20'
                        : 'bg-warning-500/10 text-warning-600 dark:text-warning-400 border-warning-500/20';
                        @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                            <td class="p-3.5 text-center text-gray-400 font-mono text-[11px]">{{ $recaps->firstItem() + $index }}</td>
                            <td class="p-3.5 font-bold text-gray-800 dark:text-gray-200">{{ $row->tanggal }}</td>
                            <td class="p-3.5 font-bold text-gray-900 dark:text-white">{{ $row->machine->nama ?? '-' }}</td>
                            <td class="p-3.5">
                                <span class="px-2.5 py-1 rounded-[4px] text-[11px] font-semibold border {{ $shiftBadgeColor }}">{{ $row->shift }}</span>
                            </td>
                            <td class="p-3.5 text-right font-semibold text-gray-800 dark:text-gray-200">{{ number_format($row->sensor1_masuk) }} pcs</td>
                            <td class="p-3.5 text-right font-bold text-emerald-600 dark:text-emerald-400">{{ number_format($row->sensor2_keluar) }} pcs</td>
                            <td class="p-3.5 text-right font-medium">
                                @if($selisih < 0)
                                    <span class="text-danger-600 dark:text-danger-400 font-bold">{{ number_format($selisih) }} pcs</span>
                                    @elseif($selisih > 0)
                                    <span class="text-emerald-600 dark:text-emerald-400 font-bold">+{{ number_format($selisih) }} pcs</span>
                                    @else
                                    <span class="text-gray-500">0 pcs</span>
                                    @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="p-8 text-center text-gray-500">Tidak ada data rekapitulasi yang sesuai dengan filter.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Pagination -->
    @if($recaps->hasPages())
    <div class="mt-4 p-4 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-[4px] shadow-sm">
        {{ $recaps->links() }}
    </div>
    @endif

    <style>
        .custom-scrollbar::-webkit-scrollbar {
            width: 5px;
            height: 5px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #d1d5db;
            border-radius: 4px;
        }

        .dark .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #4b5563;
        }
    </style>

    <script>
        function exportRekap() {
            $wire.call('exportRekap');
        }
    </script>
</div>