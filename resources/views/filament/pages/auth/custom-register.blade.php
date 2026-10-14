<x-filament-panels::page.simple>
    <x-auth-split>

        <div class="space-y-1 text-center lg:text-left">
            <h2 class="text-2xl lg:text-3xl font-bold tracking-tight text-gray-900 dark:text-white">Create an Account</h2>
            <p class="text-xs lg:text-sm text-gray-500 dark:text-gray-400">
                Or
                {{-- wire:navigate: pindah halaman tanpa memuat ulang gambar --}}
                <a href="{{ filament()->getLoginUrl() }}" wire:navigate class="font-semibold text-gray-900 dark:text-white hover:underline">sign in to your account</a>
            </p>
        </div>

        {{-- Form dan Tombol Register --}}
        <form wire:submit="register" class="space-y-4">
            {{ $this->form }}

            <x-filament::button type="submit" class="w-full justify-center bg-gray-900 hover:bg-gray-800 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-100 text-white py-2.5 px-4 rounded-xl font-medium shadow-sm transition-all duration-200">
                Sign Up
            </x-filament::button>
        </form>

    </x-auth-split>
</x-filament-panels::page.simple>