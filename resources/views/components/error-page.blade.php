@props([
'code' => 500,
'title' => null,
'description' => null,
])

@php
$code = (int) $code;

// Tema bawaan kalau Filament belum menyimpan pilihan tema di browser.
// Samakan dengan pengaturan panel kamu: 'light', 'dark', atau 'system'.
$defaultTheme = 'light';

// Saat maintenance (503) navigasi disembunyikan, diganti tombol "Try again".
// Catatan: ini hanya soal tampilan. Keamanan dijaga server (middleware maintenance).
$isMaintenance = $code === 503;

// Judul dan deskripsi bawaan berdasarkan kode error
$defaults = match ($code) {
401 => ['title' => 'Authentication required', 'desc' => "You need to sign in to access this page. Please log in and try again."],
403 => ['title' => 'Access forbidden', 'desc' => "You do not have the necessary permissions to access this control panel or view this machine's telemetry data."],
404 => ['title' => 'Page not found', 'desc' => "We couldn't find the page you were looking for. It may have been moved or deleted, or the link may be incorrect."],
419 => ['title' => 'Page expired', 'desc' => "Your session has expired due to inactivity or token mismatch. Please refresh the page and try again."],
429 => ['title' => 'Too many requests', 'desc' => "You have sent too many requests in a short time. Please wait a moment and try again."],
500 => ['title' => 'Internal server error', 'desc' => "The server encountered an internal error or misconfiguration and was unable to complete your request."],
503 => ['title' => 'Service unavailable', 'desc' => "The system is currently undergoing maintenance or firmware update. Please try again in a few moments."],
default => ['title' => 'An unexpected error occurred', 'desc' => "Something went wrong on our end. Please return to the dashboard or contact support."],
};

// Teks yang dikirim dari luar menimpa nilai bawaan
$finalTitle = $title ?? $defaults['title'];
$finalDesc = $description ?? $defaults['desc'];

// Ikon garis minimalis (gaya Lucide). Semua statis, aman dicetak dengan {!! !!}
$paths = [
'search' => '
<circle cx="11" cy="11" r="8" />
<path d="m21 21-4.3-4.3" />',
'file' => '
<path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z" />
<path d="M14 2v4a2 2 0 0 0 2 2h4" />
<path d="M10 9H8" />
<path d="M16 13H8" />
<path d="M16 17H8" />',
'pie' => '
<path d="M21.21 15.89A10 10 0 1 1 8 2.83" />
<path d="M22 12A10 10 0 0 0 12 2v10z" />',
'building' => '
<path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z" />
<path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2" />
<path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2" />
<path d="M10 6h4" />
<path d="M10 10h4" />
<path d="M10 14h4" />
<path d="M10 18h4" />',
'home' => '
<path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
<path d="M9 22V12h6v10" />',
'back' => '
<path d="m12 19-7-7 7-7" />
<path d="M19 12H5" />',
'refresh' => '
<path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8" />
<path d="M21 3v5h-5" />
<path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16" />
<path d="M8 16H3v5" />',
'chart' => '
<path d="M3 3v18h18" />
<path d="M18 17V9" />
<path d="M13 17V5" />
<path d="M8 17v-3" />',
'cpu' => '
<rect width="16" height="16" x="4" y="4" rx="2" />
<rect width="6" height="6" x="9" y="9" rx="1" />
<path d="M15 2v2" />
<path d="M15 20v2" />
<path d="M2 15h2" />
<path d="M2 9h2" />
<path d="M20 15h2" />
<path d="M20 9h2" />
<path d="M9 2v2" />
<path d="M9 20v2" />',
'settings' => '
<path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z" />
<circle cx="12" cy="12" r="3" />',
];

$svg = fn (string $name, string $class = 'w-5 h-5') =>
'<svg class="' . $class . '" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $paths[$name] . '</svg>';

// Pintasan navigasi. Ubah url sesuai halaman milikmu.
$shortcuts = [
['label' => 'Analitik Mesin', 'sub' => 'Pantau shift & hasil produksi', 'url' => '/admin', 'icon' => 'chart', 'tone' => 'bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400'],
['label' => 'Monitoring IoT', 'sub' => 'Status MQTT & sensor real-time', 'url' => '/admin', 'icon' => 'cpu', 'tone' => 'bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400'],
['label' => 'Pengaturan', 'sub' => 'Kelola profil & konfigurasi', 'url' => '/admin', 'icon' => 'settings', 'tone' => 'bg-purple-50 dark:bg-purple-950/50 text-purple-600 dark:text-purple-400'],
];
@endphp
<!DOCTYPE html>
<html lang="en" class="h-full">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $code }} - {{ $finalTitle }} | NEXORA IoT</title>

    <!--
        Tema mengikuti Filament: baca pilihan yang disimpan Filament di localStorage ("theme"),
        yaitu light / dark / system. Kalau belum ada, pakai tema bawaan ($defaultTheme).
    -->
    <script>
        (function() {
            var theme = null;
            try {
                theme = localStorage.getItem('theme');
            } catch (e) {}
            if (theme !== 'light' && theme !== 'dark' && theme !== 'system') {
                theme = '{{ $defaultTheme }}';
            }
            var dark = theme === 'dark' ||
                (theme === 'system' && window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches);
            document.documentElement.classList.toggle('dark', dark);
        })();
    </script>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Google Fonts Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif']
                    }
                }
            }
        }
    </script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }

        /* Dot pattern: titik halus yang memudar ke tepi layar */
        .dot-bg {
            background-image: radial-gradient(rgba(113, 113, 122, 0.28) 1.2px, transparent 1.2px);
            background-size: 22px 22px;
            -webkit-mask-image: radial-gradient(ellipse 75% 65% at 50% 45%, #000 15%, transparent 100%);
            mask-image: radial-gradient(ellipse 75% 65% at 50% 45%, #000 15%, transparent 100%);
        }

        .dark .dot-bg {
            background-image: radial-gradient(rgba(255, 255, 255, 0.10) 1.2px, transparent 1.2px);
        }

        /* Ikon kecil yang mengambang pelan di sekitar angka */
        @keyframes float {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-8px);
            }
        }

        .float {
            animation: float 5s ease-in-out infinite;
        }

        @media (prefers-reduced-motion: reduce) {
            .float {
                animation: none;
            }
        }
    </style>
</head>

<body class="min-h-screen bg-white dark:bg-zinc-950 text-zinc-900 dark:text-zinc-100 antialiased transition-colors duration-300">

    <!-- Lapisan dot pattern di belakang konten -->
    <div class="dot-bg pointer-events-none fixed inset-0 z-0" aria-hidden="true"></div>

    <main class="relative z-10 min-h-screen flex flex-col items-center justify-center px-4 py-12 text-center">

        <!-- Angka + ikon mengambang -->
        <div class="relative w-full max-w-lg h-[130px] sm:h-[200px] flex items-center justify-center">

            <div class="float absolute hidden sm:flex left-[10%] top-[8%] h-10 w-10 items-center justify-center rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-zinc-500 dark:text-zinc-400 shadow-sm">
                {!! $svg('search') !!}
            </div>
            <div class="float absolute hidden sm:flex right-[16%] top-[-2%] h-10 w-10 items-center justify-center rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-blue-500 shadow-sm" style="animation-delay: -1.5s">
                {!! $svg('building') !!}
            </div>
            <div class="float absolute hidden sm:flex right-[4%] top-[44%] h-10 w-10 items-center justify-center rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-zinc-500 dark:text-zinc-400 shadow-sm" style="animation-delay: -3s">
                {!! $svg('pie') !!}
            </div>
            <div class="float absolute hidden sm:flex left-[0%] top-[56%] h-10 w-10 items-center justify-center rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-emerald-500 shadow-sm" style="animation-delay: -4s">
                {!! $svg('file') !!}
            </div>

            <!-- Angka dengan gradasi yang memudar ke latar, supaya terasa menyatu -->
            <h1 class="select-none text-[110px] sm:text-[170px] font-extrabold leading-none tracking-tighter bg-gradient-to-b from-zinc-950 to-zinc-500 dark:from-white dark:to-zinc-600 bg-clip-text text-transparent">
                {{ $code }}
            </h1>
        </div>

        <!-- Judul & deskripsi -->
        <div class="mt-4 max-w-md space-y-2">
            <h2 class="text-2xl sm:text-3xl font-bold tracking-tight text-zinc-950 dark:text-white">{{ $finalTitle }}</h2>
            <p class="text-sm text-zinc-500 dark:text-zinc-400 leading-relaxed">{{ $finalDesc }}</p>

            {{-- Pesan tambahan opsional dari slot --}}
            @if (isset($slot) && $slot->isNotEmpty())
            <p class="text-sm font-medium text-zinc-700 dark:text-zinc-300 pt-1">{{ $slot }}</p>
            @endif
        </div>

        <!-- Tombol aksi -->
        @if ($isMaintenance)
        <div class="mt-7 flex justify-center">
            <button type="button" onclick="location.reload()"
                class="inline-flex items-center gap-2 rounded-lg bg-zinc-950 dark:bg-white text-white dark:text-zinc-950 px-4 py-2.5 text-sm font-medium hover:bg-zinc-800 dark:hover:bg-zinc-200 transition">
                {!! $svg('refresh', 'w-4 h-4') !!} Try again
            </button>
        </div>
        @else
        <div class="mt-7 flex flex-wrap items-center justify-center gap-3">
            <a href="{{ url('/admin') }}"
                class="inline-flex items-center gap-2 rounded-lg bg-zinc-950 dark:bg-white text-white dark:text-zinc-950 px-4 py-2.5 text-sm font-medium hover:bg-zinc-800 dark:hover:bg-zinc-200 transition">
                {!! $svg('home', 'w-4 h-4') !!} Back to dashboard
            </a>
            <button type="button" onclick="history.back()"
                class="inline-flex items-center gap-2 rounded-lg border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-zinc-800 dark:text-zinc-200 px-4 py-2.5 text-sm font-medium hover:bg-zinc-50 dark:hover:bg-zinc-800 transition">
                {!! $svg('back', 'w-4 h-4') !!} Go back
            </button>
        </div>

        @endif

        @unless ($isMaintenance)
        <!-- Quick Navigation Shortcuts -->
        <div class="relative z-10 mt-12 w-full max-w-3xl bg-zinc-50/80 dark:bg-zinc-800/40 border border-zinc-200/80 dark:border-zinc-800 rounded-xl p-5 text-left backdrop-blur-sm">
            <span class="text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider block mb-3">Where would you like to go?</span>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                @foreach ($shortcuts as $item)
                <a href="{{ url($item['url']) }}" class="p-3 rounded-lg bg-white dark:bg-zinc-900 border border-zinc-200/70 dark:border-zinc-800 hover:border-zinc-400 dark:hover:border-zinc-600 transition block">
                    <div class="flex items-center gap-2.5 mb-1">
                        <div class="w-7 h-7 rounded flex items-center justify-center {{ $item['tone'] }}">
                            {!! $svg($item['icon'], 'w-4 h-4') !!}
                        </div>
                        <span class="text-xs font-bold text-zinc-900 dark:text-zinc-100">{{ $item['label'] }}</span>
                    </div>
                    <p class="text-[11px] text-zinc-500 dark:text-zinc-400">{{ $item['sub'] }}</p>
                </a>
                @endforeach
            </div>
        </div>

        @endunless

        <footer class="mt-10 text-xs text-zinc-400 dark:text-zinc-600">
            NEXORA Enterprise IoT Monitoring System © 2026
        </footer>
    </main>

</body>

</html>