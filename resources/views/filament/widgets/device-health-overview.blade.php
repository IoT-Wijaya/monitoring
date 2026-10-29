<x-filament-widgets::widget>
    <div wire:poll.5s="updateData">
        @include('filament.widgets.components.hmi-health')
    </div>
</x-filament-widgets::widget>