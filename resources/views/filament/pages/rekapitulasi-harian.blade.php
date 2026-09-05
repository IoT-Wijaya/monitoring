<x-filament-panels::page>
    {{-- Cuma memanggil Livewire component yang sudah kamu buat sebelumnya --}}
    @livewire('rekapitulasi-harian', ['machine' => request()->query('machine')])
</x-filament-panels::page>