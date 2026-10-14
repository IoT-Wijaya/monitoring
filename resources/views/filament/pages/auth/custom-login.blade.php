<x-filament-panels::page.simple>
    <x-auth-split>

        <div class="space-y-1.5 text-center lg:text-left">
            <h2 class="text-2xl lg:text-3xl font-bold tracking-tight text-gray-900 dark:text-white">Welcome Back</h2>
            <p class="text-xs lg:text-sm text-gray-500 dark:text-gray-400">Enter your email and password to access your account</p>
        </div>

        {{-- Form dan Tombol Login --}}
        <form wire:submit="authenticate" class="space-y-5">
            {{ $this->form }}

            <x-filament::button type="submit" class="w-full justify-center bg-gray-900 hover:bg-gray-800 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-100 text-white py-2.5 px-4 rounded-xl font-medium shadow-sm transition-all duration-200">
                Sign In
            </x-filament::button>
        </form>

        {{-- Tautan Sign Up (wire:navigate: pindah halaman tanpa memuat ulang gambar) --}}
        @if (filament()->hasRegistration())
        <div class="text-center text-sm text-gray-500 dark:text-gray-400 pt-2">
            Don't have an account?
            <a href="{{ filament()->getRegistrationUrl() }}" wire:navigate class="font-semibold text-gray-900 dark:text-white hover:underline">
                Sign Up
            </a>
        </div>
        @endif

    </x-auth-split>
</x-filament-panels::page.simple>