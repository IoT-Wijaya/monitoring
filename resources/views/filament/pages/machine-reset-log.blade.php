<x-filament-panels::page class="max-w-none">

    @assets
    {{-- Flatpickr CSS & JS --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    @endassets

    <div class="space-y-6">

        {{-- FILTER CARD --}}
        <div class="p-4 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-[4px] shadow-sm">
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
                        <input x-ref="dateRange" type="text" placeholder="Pilih Rentang Tanggal..." class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-300 dark:border-gray-700 text-xs text-gray-900 dark:text-gray-100 rounded-[4px] px-3 py-2.5 outline-none focus:ring-2 focus:ring-primary-500">
                    </div>

                    {{-- Searchable Select Machine --}}
                    <div x-data="{ 
                        open: false, 
                        search: '', 
                        selected: @entangle('machineId').live,
                        items: {{ \App\Models\Machine::orderBy('nama')->get()->map(fn($m) => ['id' => $m->id, 'label' =>$m->nama])->toJson() }},
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

                    {{-- Target Reset Filter & Reset Button Row --}}
                    <div class="grid grid-cols-2 sm:flex sm:items-center gap-2 w-full sm:w-auto">
                        <div class="w-full sm:w-auto">
                            <select wire:model.live="targetReset" class="w-full sm:w-auto bg-gray-50 dark:bg-gray-800 border border-gray-300 dark:border-gray-700 text-xs text-gray-900 dark:text-gray-100 rounded-[4px] px-3 py-2.5 outline-none focus:ring-2 focus:ring-primary-500 cursor-pointer">
                                <option value="">Semua Target Reset</option>
                                <option value="Reset Semua">Reset Semua</option>
                                <option value="Sensor 1">Sensor 1</option>
                                <option value="Sensor 2">Sensor 2</option>
                            </select>
                        </div>
                        <div class="w-full sm:w-auto">
                            <x-filament::button color="gray" wire:click="resetFilters" size="sm" icon="heroicon-m-arrow-path" class="rounded-[4px] w-full sm:w-auto justify-center">
                                Reset
                            </x-filament::button>
                        </div>
                    </div>
                </div>

                {{-- Total Counter Badge --}}
                <div class="text-xs text-gray-500 dark:text-gray-400 font-medium px-3.5 py-2 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-[4px] text-center lg:text-left shrink-0">
                    Total: <span class="font-bold text-amber-600 dark:text-amber-400">{{ $this->logs->total() }}</span> Log Reset
                </div>

            </div>
        </div>

        {{-- RESPONSIVE VIEW: CARD LIST UNTUK MOBILE & TABEL UNTUK DESKTOP --}}
        <div>
            @forelse($this->logs as $index =>$log)
            @php
            $badgeColor = match($log->target) {
            'Reset Semua' => 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/20',
            'Sensor 1' => 'bg-blue-500/10 text-blue-600 dark:text-blue-400 border-blue-500/20',
            'Sensor 2' => 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20',
            default => 'bg-gray-500/10 text-gray-600 dark:text-gray-400 border-gray-500/20',
            };
            @endphp

            {{-- TAMPILAN MOBILE (Card View - Hanya tampil di layar kecil md:hidden) --}}
            <div class="block md:hidden p-3.5 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-[6px] shadow-sm space-y-2.5 mb-3">
                <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-800 pb-2">
                    <div class="flex items-center gap-2">
                        <span class="text-[10px] font-mono text-gray-400 font-bold">#{{ $this->logs->firstItem() +$index }}</span>
                        <span class="text-xs font-bold text-gray-800 dark:text-gray-200">{{ $log->created_at->format('Y-m-d H:i:s') }}</span>
                    </div>
                    <span class="px-2 py-0.5 rounded text-[10px] font-semibold border {{ $badgeColor }}">{{ $log->target }}</span>
                </div>
                <div>
                    <div class="text-xs font-black text-gray-900 dark:text-white truncate">{{ $log->machine->nama ?? '-' }}</div>
                    <div class="text-[11px] text-gray-500 dark:text-gray-400 font-medium">Operator: {{ $log->user->name ?? 'System / Admin' }}</div>
                </div>
                <div class="grid grid-cols-2 gap-2 pt-1.5 border-t border-gray-100 dark:border-gray-800 text-center">
                    <div class="bg-gray-50 dark:bg-gray-800/60 p-1.5 rounded">
                        <div class="text-[9px] text-gray-400 uppercase font-semibold">S1 Sebelum</div>
                        <div class="text-[11px] font-bold text-gray-700 dark:text-gray-200">{{ number_format($log->jumlah_s1_sebelum_reset) }} pcs</div>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-800/60 p-1.5 rounded">
                        <div class="text-[9px] text-gray-400 uppercase font-semibold">S2 Sebelum</div>
                        <div class="text-[11px] font-bold text-emerald-600 dark:text-emerald-400">{{ number_format($log->jumlah_s2_sebelum_reset) }} pcs</div>
                    </div>
                </div>
            </div>
            @empty
            <div class="block md:hidden p-8 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-[6px] text-center text-xs text-gray-500 mb-3">
                Tidak ada data log reset mesin yang ditemukan.
            </div>
            @endforelse

            {{-- TAMPILAN DESKTOP (Tabel Klasik - Hanya tampil di ukuran md ke atas) --}}
            <div class="hidden md:block bg-[#f3f4f6] dark:bg-[#121214] border border-gray-200 dark:border-gray-800 rounded-[4px] overflow-hidden shadow-sm transition-colors duration-200">
                <div class="bg-white dark:bg-gray-900 overflow-x-auto">
                    <table class="w-full text-left border-collapse min-w-[700px]">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-800/60 border-b border-gray-200 dark:border-gray-800 text-[11px] uppercase tracking-wider text-gray-500 dark:text-gray-400 font-semibold">
                                <th class="p-3.5 w-12 text-center">#</th>
                                <th class="p-3.5">Waktu & Tanggal</th>
                                <th class="p-3.5">Nama Mesin</th>
                                <th class="p-3.5">Operator (User)</th>
                                <th class="p-3.5">Target Reset</th>
                                <th class="p-3.5 text-right">Nilai S1 Sebelum</th>
                                <th class="p-3.5 text-right">Nilai S2 Sebelum</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800 text-xs">
                            @forelse($this->logs as $index =>$log)
                            @php
                            $badgeColor = match($log->target) {
                            'Reset Semua' => 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/20',
                            'Sensor 1' => 'bg-blue-500/10 text-blue-600 dark:text-blue-400 border-blue-500/20',
                            'Sensor 2' => 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20',
                            default => 'bg-gray-500/10 text-gray-600 dark:text-gray-400 border-gray-500/20',
                            };
                            @endphp
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                                <td class="p-3.5 text-center text-gray-400 font-mono text-[11px]">{{ $this->logs->firstItem() +$index }}</td>
                                <td class="p-3.5 font-bold text-gray-800 dark:text-gray-200">{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                                <td class="p-3.5 font-bold text-gray-900 dark:text-white">{{ $log->machine->nama ?? '-' }}</td>
                                <td class="p-3.5 text-gray-700 dark:text-gray-300 font-medium">{{ $log->user->name ?? 'System / Admin' }}</td>
                                <td class="p-3.5">
                                    <span class="px-2.5 py-1 rounded-[4px] text-[11px] font-semibold border {{ $badgeColor }}">
                                        {{ $log->target }}
                                    </span>
                                </td>
                                <td class="p-3.5 text-right font-semibold text-gray-800 dark:text-gray-200">{{ number_format($log->jumlah_s1_sebelum_reset) }} pcs</td>
                                <td class="p-3.5 text-right font-semibold text-gray-800 dark:text-gray-200">{{ number_format($log->jumlah_s2_sebelum_reset) }} pcs</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="p-8 text-center text-gray-500">Tidak ada data log reset mesin yang ditemukan.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Pagination --}}
            @if($this->logs->hasPages())
            <div class="mt-4 p-4 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-[4px] shadow-sm text-xs text-gray-500">
                {{ $this->logs->links() }}
            </div>
            @endif
        </div>

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
</x-filament-panels::page>