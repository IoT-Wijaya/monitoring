<div>
    @assets
    {{-- Flatpickr CSS & JS --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    @endassets

    <div class="space-y-6">

        {{-- FILTER CARD --}}
        <div class="p-4 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-md shadow-2xs">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">

                {{-- Left Side: Filters --}}
                <div class="flex flex-col sm:flex-row sm:flex-wrap items-stretch lg:items-center gap-3 flex-1">

                    {{-- Date Picker Filter --}}
                    <div class="relative w-full sm:w-auto sm:min-w-[240px]" x-data x-init="
                        flatpickr($refs.dateRange, {
                            mode: 'range',
                            dateFormat: 'Y-m-d',
                            defaultDate: [
                                '{{ $tanggalDari ?? now()->format('Y-m-d') }}', 
                                '{{ $tanggalSampai ?? now()->format('Y-m-d') }}'
                            ],
                            altInput: true,
                            altFormat: 'd-m-Y',
                            conjunction: ' to ',
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

                    {{-- Searchable Select Machine --}}
                    <div x-data="{ 
                        open: false, 
                        search: '', 
                        selected: @entangle('machineId').live,
                        items: {{ \App\Models\Machine::orderBy('nama')->get()->map(fn($m) => ['id' => $m->id, 'label' => $m->nama])->toJson() }},
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

                    {{-- Filter Jenis Event --}}
                    <div class="w-full sm:w-auto sm:min-w-[150px]">
                        <select wire:model.live="jenis" class="w-full bg-gray-50 dark:bg-gray-800/60 border border-gray-200 dark:border-gray-700 text-xs text-gray-900 dark:text-gray-100 rounded-md px-3 py-2.5 outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-gray-100 cursor-pointer">
                            <option value="">Semua Jenis</option>
                            <option value="jaringan">Jaringan</option>
                            <option value="sensor1">Sensor 1</option>
                            <option value="sensor2">Sensor 2</option>
                        </select>
                    </div>

                    {{-- Filter Status & Reset Button Row --}}
                    <div class="grid grid-cols-2 sm:flex sm:items-center gap-2 w-full sm:w-auto">
                        <div class="w-full sm:w-auto sm:min-w-[140px]">
                            <select wire:model.live="status" class="w-full bg-gray-50 dark:bg-gray-800/60 border border-gray-200 dark:border-gray-700 text-xs text-gray-900 dark:text-gray-100 rounded-md px-3 py-2.5 outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-gray-100 cursor-pointer">
                                <option value="">Semua Status</option>
                                <option value="normal">Normal</option>
                                <option value="gangguan">Gangguan</option>
                            </select>
                        </div>
                        <div class="w-full sm:w-auto">
                            <x-filament::button color="gray" wire:click="resetFilters" size="sm" icon="heroicon-m-arrow-path" class="rounded-md w-full sm:w-auto justify-center">
                                Reset
                            </x-filament::button>
                        </div>
                    </div>
                </div>

                {{-- Total Counter Badge --}}
                <div class="flex items-center justify-center lg:justify-end text-xs font-medium shrink-0">
                    <div class="px-3 py-2 bg-gray-50 dark:bg-gray-800/50 border border-gray-200/80 dark:border-gray-700/80 rounded-md text-gray-500 dark:text-gray-400 w-full lg:w-auto text-center">
                        Total: <span class="font-mono font-bold text-indigo-600 dark:text-indigo-400">{{ $this->logs->total() }}</span> Event
                    </div>
                </div>

            </div>
        </div>

        {{-- RESPONSIVE VIEW: CARD LIST UNTUK MOBILE & TABEL UNTUK DESKTOP --}}
        <div>
            @forelse($this->logs as $index => $log)
            @php
            $statusColor = match($log->status) {
            'gangguan' => 'inline-flex items-center px-2.5 py-0.5 rounded-md text-[11px] font-medium bg-red-50 text-red-700 border border-red-200/60 dark:bg-red-500/10 dark:text-red-400 dark:border-red-500/20',
            'normal' => 'inline-flex items-center px-2.5 py-0.5 rounded-md text-[11px] font-medium bg-emerald-50 text-emerald-700 border border-emerald-200/60 dark:bg-emerald-500/10 dark:text-emerald-400 dark:border-emerald-500/20',
            default => 'inline-flex items-center px-2.5 py-0.5 rounded-md text-[11px] font-medium bg-gray-50 text-gray-700 border border-gray-200/60 dark:bg-gray-500/10 dark:text-gray-400 dark:border-gray-500/20',
            };
            $jenisLabel = match($log->jenis) {
            'jaringan' => 'Jaringan',
            'sensor1' => 'Sensor 1',
            'sensor2' => 'Sensor 2',
            default => $log->jenis,
            };
            @endphp

            {{-- TAMPILAN MOBILE (Card View) --}}
            <div class="block md:hidden p-3.5 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-md shadow-2xs space-y-2.5 mb-3">
                <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-800 pb-2">
                    <div class="flex items-center gap-2">
                        <span class="text-[10px] font-mono text-gray-400 font-bold">#{{ $this->logs->firstItem() + $index }}</span>
                        <span class="text-xs font-semibold text-gray-800 dark:text-gray-200">{{ $log->terjadi_pada->format('d-m-Y H:i:s') }}</span>
                    </div>
                    <span class="{{ $statusColor }}">{{ ucfirst($log->status) }}</span>
                </div>
                <div>
                    <div class="text-xs font-semibold text-gray-900 dark:text-gray-100 truncate">{{ $log->machine->nama ?? '-' }}</div>
                    <div class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5">Jenis: <span class="font-medium text-gray-700 dark:text-gray-300">{{ $jenisLabel }}</span></div>
                </div>
                <div class="pt-1.5 border-t border-gray-100 dark:border-gray-800 text-[11px] text-gray-600 dark:text-gray-400">
                    <span class="font-semibold text-gray-400 uppercase text-[9px] block">Keterangan:</span>
                    <p class="mt-0.5 text-gray-700 dark:text-gray-300">{{ $log->keterangan ?? '-' }}</p>
                </div>
            </div>
            @empty
            <div class="block md:hidden p-6 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-md text-center text-xs text-gray-500 mb-3">
                Tidak ada data event log yang ditemukan.
            </div>
            @endforelse

            {{-- TAMPILAN DESKTOP (Clean Shadcn Table) --}}
            <div class="hidden md:block bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-md overflow-hidden shadow-2xs">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse min-w-[700px]">
                        <thead>
                            <tr class="border-b border-gray-200 dark:border-gray-800 text-[13px] text-gray-500 dark:text-gray-400 font-medium">
                                <th class="p-4 w-12 text-center font-mono">#</th>
                                <th class="p-4 font-medium">Waktu Kejadian</th>
                                <th class="p-4 font-medium">Nama Mesin</th>
                                <th class="p-4 font-medium">Jenis</th>
                                <th class="p-4 font-medium">Status</th>
                                <th class="p-4 font-medium">Keterangan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800/60 text-xs">
                            @forelse($this->logs as $index => $log)
                            @php
                            $statusColor = match($log->status) {
                            'gangguan' => 'inline-flex items-center px-2.5 py-0.5 rounded-md text-[11px] font-medium bg-red-50 text-red-700 border border-red-200/60 dark:bg-red-500/10 dark:text-red-400 dark:border-red-500/20',
                            'normal' => 'inline-flex items-center px-2.5 py-0.5 rounded-md text-[11px] font-medium bg-emerald-50 text-emerald-700 border border-emerald-200/60 dark:bg-emerald-500/10 dark:text-emerald-400 dark:border-emerald-500/20',
                            default => 'inline-flex items-center px-2.5 py-0.5 rounded-md text-[11px] font-medium bg-gray-50 text-gray-700 border border-gray-200/60 dark:bg-gray-500/10 dark:text-gray-400 dark:border-gray-500/20',
                            };
                            $jenisLabel = match($log->jenis) {
                            'jaringan' => 'Jaringan',
                            'sensor1' => 'Sensor 1',
                            'sensor2' => 'Sensor 2',
                            default => $log->jenis,
                            };
                            @endphp
                            <tr class="hover:bg-gray-50/60 dark:hover:bg-gray-800/40 transition-colors">
                                <td class="p-4 text-center text-gray-400 font-mono text-[11px]">{{ $this->logs->firstItem() + $index }}</td>
                                <td class="p-4 font-mono text-gray-600 dark:text-gray-400">{{ $log->terjadi_pada->format('d-m-Y H:i:s') }}</td>
                                <td class="p-4 font-semibold text-gray-900 dark:text-white">{{ $log->machine->nama ?? '-' }}</td>
                                <td class="p-4 text-gray-700 dark:text-gray-300 font-medium">{{ $jenisLabel }}</td>
                                <td class="p-4">
                                    <span class="{{ $statusColor }}">
                                        {{ ucfirst($log->status) }}
                                    </span>
                                </td>
                                <td class="p-4 text-gray-600 dark:text-gray-400">{{ $log->keterangan ?? '-' }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="p-6 text-center text-gray-400">Tidak ada data event log yang ditemukan.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Pagination --}}
        @if($this->logs->hasPages())
        <div class="mt-4 p-4 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-md shadow-2xs">
            {{ $this->logs->links() }}
        </div>
        @endif
    </div>

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