<x-filament-panels::page>
    {{-- SECTION 1: COUNTING --}}
    <section class="space-y-3">
        <h2 class="text-sm font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
            Monitoring Produksi (Counting)
        </h2>

        <x-filament-widgets::widgets
            :widgets="$this->getCountingWidgets()"
            :columns="['default' => 1, 'lg' => 2]"
            :data="$this->getWidgetData()" />
    </section>

    {{-- SECTION 2: HMI HEALTH CHECK --}}
    <section class="space-y-3">
        <h2 class="text-sm font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
            HMI Health Check
        </h2>

        <x-filament-widgets::widgets
            :widgets="$this->getHmiWidgets()"
            :columns="['default' => 1, 'lg' => 2]"
            :data="$this->getWidgetData()" />
    </section>
</x-filament-panels::page>