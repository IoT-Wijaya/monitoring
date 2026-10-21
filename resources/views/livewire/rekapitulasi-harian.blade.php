<div>
    @assets
    {{-- Flatpickr CSS & JS --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    @endassets

    <!-- Header Page -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <x-filament::button color="success" icon="heroicon-m-arrow-down-tray" wire:click="exportRekap" class="rounded-md w-full sm:w-auto shadow-2xs text-zinc-50 [&>svg]:text-zinc-50">
            Export Data
        </x-filament::button>
    </div>

    <!-- Filter Card -->
    <div class="p-4 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-md shadow-2xs flex flex-col lg:flex-row lg:items-center justify-between gap-4 mb-6">
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
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                    <i class="fa-regular fa-calendar text-xs"></i>
                </div>
                <input x-ref="dateRange" type="text" placeholder="Pilih Rentang Tanggal..." class="w-full bg-gray-50 dark:bg-gray-800/60 border border-gray-200 dark:border-gray-700 text-xs text-gray-900 dark:text-gray-100 rounded-md pl-9 pr-3 py-2.5 outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-gray-100">
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
            }" class="relative w-full sm:w-auto sm:min-w-[240px]">

                <button @click="open = !open" type="button" class="w-full text-xs bg-gray-50 dark:bg-gray-800/60 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-gray-100 rounded-md px-3 py-2.5 flex justify-between items-center outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-gray-100 cursor-pointer">
                    <span class="font-medium truncate" x-text="selected ? items.find(i => i.id == selected)?.label : 'Semua Mesin'"></span>
                    <svg class="w-3 h-3 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>

                <div x-show="open" x-transition @click.away="open = false" class="absolute z-50 mt-1 w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 shadow-xl rounded-md overflow-hidden" style="display: none;">
                    <div class="p-2 border-b border-gray-100 dark:border-gray-800 bg-gray-50 dark:bg-gray-950 flex items-center relative">
                        <input x-model="search" type="text" placeholder="Cari nama mesin..." class="w-full bg-white dark:bg-gray-800 text-xs border border-gray-200 dark:border-gray-700 rounded-md p-2 pr-7 outline-none text-gray-900 dark:text-gray-100">
                        <button x-show="search.length > 0" @click="search = ''" class="absolute right-4 text-gray-400 hover:text-red-500 cursor-pointer">×</button>
                    </div>
                    <div class="max-h-60 overflow-y-auto custom-scrollbar font-sans text-xs">
                        <div @click="selected = ''; open = false; search = ''" class="px-3 py-2 text-[11px] font-semibold text-gray-400 uppercase hover:bg-gray-50 dark:hover:bg-gray-800 cursor-pointer">Semua Mesin</div>
                        <template x-for="item in filteredItems" :key="item.id">
                            <div @click="selected = item.id; open = false; search = ''" class="px-3 py-2.5 hover:bg-gray-50 dark:hover:bg-gray-800/60 cursor-pointer border-b border-gray-100 dark:border-gray-800/50 last:border-0 transition-colors">
                                <span class="font-medium text-gray-800 dark:text-gray-200" x-text="item.label"></span>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Shift Dropdown & Reset Button -->
            <div class="grid grid-cols-2 gap-2 sm:flex sm:items-center">
                <div class="w-full sm:w-auto">
                    <select wire:model.live="shift" class="w-full sm:w-auto bg-gray-50 dark:bg-gray-800/60 border border-gray-200 dark:border-gray-700 text-xs text-gray-900 dark:text-gray-100 rounded-md px-3 py-2.5 outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-gray-100 cursor-pointer">
                        <option value="">Semua Shift</option>
                        <option value="Shift Pagi">Shift Pagi</option>
                        <option value="Shift Malam">Shift Malam</option>
                    </select>
                </div>
                <div class="w-full sm:w-auto">
                    <x-filament::button color="gray" wire:click="resetFilter" size="sm" icon="heroicon-m-arrow-path" class="rounded-md w-full sm:w-auto justify-center">
                        Reset
                    </x-filament::button>
                </div>
            </div>
        </div>

        <!-- Total Counter Badges (Dibalik: Masuk pakai $total->keluar, Keluar pakai $total->masuk) -->
        <div class="flex flex-wrap items-center justify-center lg:justify-end gap-2 text-xs font-medium">
            <div class="px-3 py-2 bg-gray-50 dark:bg-gray-800/50 border border-gray-200/80 dark:border-gray-700/80 rounded-md text-gray-500 dark:text-gray-400">
                Masuk: <span class="font-mono font-bold text-gray-800 dark:text-gray-200">{{ number_format((int) $total->keluar) }}</span>
            </div>
            <div class="px-3 py-2 bg-gray-50 dark:bg-gray-800/50 border border-gray-200/80 dark:border-gray-700/80 rounded-md text-gray-500 dark:text-gray-400">
                Keluar: <span class="font-mono font-bold text-emerald-600 dark:text-emerald-400">{{ number_format((int) $total->masuk) }}</span>
            </div>
            <div class="px-3 py-2 bg-gray-50 dark:bg-gray-800/50 border border-gray-200/80 dark:border-gray-700/80 rounded-md text-gray-500 dark:text-gray-400">
                <span class="font-mono font-bold text-indigo-600 dark:text-indigo-400">{{ $recaps->total() }}</span> Data Rekap
            </div>
        </div>
    </div>

    <!-- RESPONSIVE VIEW -->
    <div>
        @forelse($recaps as $index => $row)
        @php
        // Selisih dibalik: (sensor1_masuk - sensor2_keluar)
        $selisih = $row->sensor1_masuk - $row->sensor2_keluar;
        $isMalam = $row->shift === 'Shift Malam';

        $shiftBadgeClass = $isMalam
        ? 'inline-flex items-center px-2.5 py-0.5 rounded-md text-[11px] font-medium bg-purple-50 text-purple-700 border border-purple-200/60 dark:bg-purple-500/10 dark:text-purple-400 dark:border-purple-500/20'
        : 'inline-flex items-center px-2.5 py-0.5 rounded-md text-[11px] font-medium bg-amber-50 text-amber-700 border border-amber-200/60 dark:bg-amber-500/10 dark:text-amber-400 dark:border-amber-500/20';
        @endphp

        <!-- TAMPILAN MOBILE (Card View) -->
        <div class="block md:hidden p-3.5 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-md shadow-2xs space-y-2.5 mb-3">
            <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-800 pb-2">
                <div class="flex items-center gap-2">
                    <span class="text-[10px] font-mono text-gray-400 font-bold">#{{ $recaps->firstItem() + $index }}</span>
                    <span class="text-xs font-semibold text-gray-800 dark:text-gray-200">{{ \Carbon\Carbon::parse($row->tanggal)->format('d-m-Y') }}</span>
                    <span class="text-[10px] font-mono text-gray-500">{{ $row->jam_label }}</span>
                </div>
                <span class="{{ $shiftBadgeClass }}">{{ $row->shift }}</span>
            </div>
            <div>
                <div class="text-xs font-semibold text-gray-900 dark:text-gray-100 truncate">{{ $row->machine->nama ?? '-' }}</div>
            </div>
            <div class="grid grid-cols-3 gap-1.5 pt-1.5 border-t border-gray-100 dark:border-gray-800 text-center">
                <!-- Kolom Keluar menampilkan sensor1_masuk -->
                <div class="bg-gray-50 dark:bg-gray-800/40 p-1.5 rounded-md">
                    <div class="text-[9px] text-gray-400 uppercase font-semibold">Keluar</div>
                    <div class="text-[12px] font-mono font-bold text-emerald-600 dark:text-emerald-400">{{ number_format($row->sensor1_masuk) }}</div>
                </div>
                <!-- Kolom Masuk menampilkan sensor2_keluar -->
                <div class="bg-gray-50 dark:bg-gray-800/40 p-1.5 rounded-md">
                    <div class="text-[9px] text-gray-400 uppercase font-semibold">Masuk</div>
                    <div class="text-[12px] font-mono font-semibold text-gray-700 dark:text-gray-300">{{ number_format($row->sensor2_keluar) }}</div>
                </div>
                <div class="bg-gray-50 dark:bg-gray-800/40 p-1.5 rounded-md">
                    <div class="text-[9px] text-gray-400 uppercase font-semibold">Selisih</div>
                    <div class="text-[12px] font-mono font-bold">
                        @if($selisih < 0)
                            <span class="text-red-600 dark:text-red-400">{{ number_format($selisih) }}</span>
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
        <div class="block md:hidden p-6 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-md text-center text-xs text-gray-500">
            Tidak ada data rekapitulasi yang sesuai dengan filter.
        </div>
        @endforelse

        <!-- TAMPILAN DESKTOP (Table View) -->
        <div class="hidden md:block bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-md overflow-hidden shadow-2xs">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse min-w-[700px]">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-gray-800 text-[13px] text-gray-500 dark:text-gray-400 font-medium">
                            <th class="p-4 w-12 text-center font-mono">#</th>
                            <th class="p-4 font-medium">Tanggal</th>
                            <th class="p-4 font-medium">Jam</th>
                            <th class="p-4 font-medium">Nama Mesin</th>
                            <th class="p-4 font-medium">Status Shift</th>
                            <!-- Header Kolom Dibalik -->
                            <th class="p-4 text-right font-medium">Keluar</th>
                            <th class="p-4 text-right font-medium">Masuk</th>
                            <th class="p-4 text-right font-medium">Selisih</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800/60 text-xs">
                        @forelse($recaps as $index => $row)
                        @php
                        $selisih = $row->sensor1_masuk - $row->sensor2_keluar;
                        $isMalam = $row->shift === 'Shift Malam';

                        $shiftBadgeClass = $isMalam
                        ? 'inline-flex items-center px-2.5 py-0.5 rounded-md text-[11px] font-medium bg-purple-50 text-purple-700 border border-purple-200/60 dark:bg-purple-500/10 dark:text-purple-400 dark:border-purple-500/20'
                        : 'inline-flex items-center px-2.5 py-0.5 rounded-md text-[11px] font-medium bg-amber-50 text-amber-700 border border-amber-200/60 dark:bg-amber-500/10 dark:text-amber-400 dark:border-amber-500/20';
                        @endphp
                        <tr class="hover:bg-gray-50/60 dark:hover:bg-gray-800/40 transition-colors">
                            <td class="p-4 text-center text-gray-400 font-mono text-[11px]">{{ $recaps->firstItem() + $index }}</td>
                            <td class="p-4 font-semibold text-gray-800 dark:text-gray-200">{{ \Carbon\Carbon::parse($row->tanggal)->format('d-m-Y') }}</td>
                            <td class="p-4 font-mono text-gray-600 dark:text-gray-400">{{ $row->jam_label }}</td>
                            <td class="p-4 font-semibold text-gray-900 dark:text-white">{{ $row->machine->nama ?? '-' }}</td>
                            <td class="p-4">
                                <span class="{{ $shiftBadgeClass }}">{{ $row->shift }}</span>
                            </td>
                            <!-- Keluar diisi dengan nilai sensor1_masuk -->
                            <td class="p-4 text-right font-mono font-bold text-emerald-600 dark:text-emerald-400">{{ number_format($row->sensor1_masuk) }} pcs</td>
                            <!-- Masuk diisi dengan nilai sensor2_keluar -->
                            <td class="p-4 text-right font-mono font-semibold text-gray-700 dark:text-gray-300">{{ number_format($row->sensor2_keluar) }} pcs</td>
                            <td class="p-4 text-right font-mono font-medium">
                                @if($selisih < 0)
                                    <span class="text-red-600 dark:text-red-400 font-bold">{{ number_format($selisih) }} pcs</span>
                                    @elseif($selisih > 0)
                                    <span class="text-emerald-600 dark:text-emerald-400 font-bold">+{{ number_format($selisih) }} pcs</span>
                                    @else
                                    <span class="text-gray-400">0 pcs</span>
                                    @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="p-6 text-center text-gray-400">Tidak ada data rekapitulasi yang sesuai dengan filter.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Pagination -->
    @if($recaps->hasPages())
    <div class="mt-4 p-4 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-md shadow-2xs">
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
</div>