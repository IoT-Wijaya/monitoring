<div class="fixed inset-0 z-50 overflow-hidden bg-white dark:bg-gray-950 font-[-apple-system,BlinkMacSystemFont,'SF_Pro_Display','Segoe_UI',Roboto,Helvetica,Arial,sans-serif]">

    {{-- SISI KIRI: Gambar & Tagline (tetap di tempat, hanya tampil di desktop) --}}
    @persist('auth-visual')
    <div class="hidden lg:flex fixed inset-y-0 left-0 w-1/2 bg-black items-end p-12 overflow-hidden">
        <!-- Background Image Astronot -->
        <img src="{{ asset('images/login-profile.png') }}"
            alt="Astronaut Illustration"
            loading="lazy"
            decoding="async"
            class="absolute inset-0 w-full h-full object-cover opacity-90 transition-transform duration-700 hover:scale-105">

        <!-- Gradasi Overlay -->
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
    @endpersist

    {{-- SISI KANAN: bagian yang berganti (logo + isi dari slot) --}}
    <div class="h-full w-full lg:w-1/2 lg:ml-auto flex flex-col justify-center items-center p-6 sm:p-12 overflow-y-auto bg-white dark:bg-gray-900 transition-colors duration-300">
        <div class="w-full max-w-md space-y-4 lg:space-y-6">

            {{-- Logo NEXORA --}}
            <div class="flex items-center gap-3 justify-center lg:justify-start">
                <div class="w-8 h-8 lg:w-9 lg:h-9 rounded-full bg-black dark:bg-white flex items-center justify-center shadow-md transition-colors duration-300">
                    <svg class="w-4 h-4 lg:w-5 lg:h-5 text-white dark:text-black transition-colors duration-300" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M2 7C5 5 7 9 10 7C13 5 15 9 18 7C20 6 21.5 7 22 7.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                        <path d="M2 12C5 10 7 14 10 12C13 10 15 14 18 12C20 11 21.5 12 22 12.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                        <path d="M2 17C5 15 7 19 10 17C13 15 15 19 18 17C20 16 21.5 17 22 17.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                    </svg>
                </div>
                <span class="text-lg lg:text-xl font-bold text-gray-900 dark:text-white uppercase">NEXORA</span>
            </div>

            {{ $slot }}

        </div>
    </div>

</div>