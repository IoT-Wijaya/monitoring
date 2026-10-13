<x-filament-panels::page.simple>
    <div class="fixed inset-0 z-50 flex overflow-hidden bg-white dark:bg-gray-950 font-[-apple-system,BlinkMacSystemFont,'SF_Pro_Display','Segoe_UI',Roboto,Helvetica,Arial,sans-serif]">

        {{-- SISI KIRI: Gambar Ilustrasi Astronot (Disembunyikan di Mobile, Tampil di Tablet/Desktop) --}}
        <div class="hidden lg:flex lg:w-1/2 relative bg-black items-end p-12 overflow-hidden">
            <!-- Background Image Astronot -->
            <img src="{{ asset('images/login-profile.png') }}"
                alt="Astronaut Illustration"
                class="absolute inset-0 w-full h-full object-cover opacity-90 transition-transform duration-700 hover:scale-105">

            <!-- Gradasi Overlay dinamis untuk Light/Dark -->
            <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent"></div>

            <!-- Konten Teks di Atas Gambar -->
            <div class="relative z-10 text-white max-w-lg">
                <span class="text-xs uppercase tracking-widest text-teal-300 font-semibold mb-2 block">A Wise Quote</span>
                <h1 class="text-4xl font-bold tracking-tight leading-tight mb-4">
                    Get Everything You Want
                </h1>
                <p class="text-sm text-gray-300 font-normal leading-relaxed">
                    You can get everything you want if you work hard, trust the process, and stick to the plan.
                </p>
            </div>
        </div>

        {{-- SISI KANAN: Form Login dengan Logo Unik NEXORA & Tombol Sign Up --}}
        <div class="w-full lg:w-1/2 flex flex-col justify-center items-center p-8 sm:p-12 overflow-y-auto bg-white dark:bg-gray-900 transition-colors duration-300">
            <div class="w-full max-w-md space-y-6">

                {{-- Logo NEXORA (Ikon Bulat Hitam dengan Pola Unik Kustom + Teks) --}}
                <div class="flex items-center gap-3 justify-center lg:justify-start">
                    <!-- Lingkaran Hitam dengan Pola Garis Unik di dalamnya -->
                    <div class="w-9 h-9 rounded-full bg-black dark:bg-white flex items-center justify-center shadow-md transition-colors duration-300">
                        <svg class="w-5 h-5 text-white dark:text-black transition-colors duration-300" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <!-- Pola Gelombang/Garis Unik yang Berbeda -->
                            <path d="M2 7C5 5 7 9 10 7C13 5 15 9 18 7C20 6 21.5 7 22 7.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                            <path d="M2 12C5 10 7 14 10 12C13 10 15 14 18 12C20 11 21.5 12 22 12.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                            <path d="M2 17C5 15 7 19 10 17C13 15 15 19 18 17C20 16 21.5 17 22 17.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                        </svg>
                    </div>
                    <!-- Nama Brand -->
                    <span class="text-xl font-bold text-gray-900 dark:text-white uppercase">NEXORA</span>
                </div>

                <div class="space-y-1.5 text-center lg:text-left">
                    <h2 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white">Welcome Back</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Enter your email and password to access your account</p>
                </div>

                {{-- Form dan Tombol Login Gaya Apple --}}
                <form wire:submit="authenticate" class="space-y-5">
                    {{ $this->form }}

                    <x-filament::button type="submit" class="w-full justify-center bg-gray-900 hover:bg-gray-800 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-100 text-white py-2.5 px-4 rounded-xl font-medium shadow-sm transition-all duration-200">
                        Sign In
                    </x-filament::button>
                </form>

                {{-- Tautan Sign Up Bawaan Filament (jika fitur registrasi diaktifkan di panel) --}}
                @if (filament()->hasRegistration())
                <div class="text-center text-sm text-gray-500 dark:text-gray-400 pt-2">
                    Don't have an account?
                    <a href="{{ filament()->getRegistrationUrl() }}" class="font-semibold text-gray-900 dark:text-white hover:underline">
                        Sign Up
                    </a>
                </div>
                @endif

            </div>
        </div>

    </div>
</x-filament-panels::page.simple>