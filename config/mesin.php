<?php

// Simpan sebagai config/mesin.php, lalu jalankan: php artisan config:clear

return [
    // Sensor dinilai "tidak menghitung" bila nol selama N menit penuh
    // sementara sensor lawannya tetap menghitung.
    'diam_menit' => 10,

    // Minimal hitungan sensor lawan (pada paruh awal jendela) agar dianggap aktif.
    'ambang_lawan' => 10,

    // Batas hitungan per menit yang masih masuk akal untuk mesin.
    // ISI DENGAN KAPASITAS NYATA MESIN (lihat rekap per jam tertinggi dibagi 60, lalu beri kelonggaran).
    'batas_per_menit' => [
        'default' => 120,
        'veneer'  => 120,
    ],
];
