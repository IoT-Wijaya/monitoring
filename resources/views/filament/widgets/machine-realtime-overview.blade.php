<x-filament-widgets::widget>
    <x-filament::section wire:poll.1s="updateData" class="!p-0 !bg-transparent !shadow-none">

        <!-- 1. Modul Monitoring Produksi -->
        @include('filament.widgets.components.machine-monitoring')

    </x-filament::section>
</x-filament-widgets::widget>