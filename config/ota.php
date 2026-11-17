<?php

return [
    // Manifest rilis terbaru yang dibuat CI: [64 byte tanda tangan][JSON].
    // Di VPS ini berkas yang sama yang disajikan nginx di /fw/manifest.signed.
    'manifest_path' => env('OTA_MANIFEST_PATH', '/var/www/firmware/manifest.signed'),

    // Kunci publik Ed25519 (32 byte, heksadesimal 64 karakter): pasangan kunci rahasia
    // di GitHub Secret OTA_SIGNING_KEY, sama dengan include/ota_public_key.h di firmware.
    'public_key_hex' => env('OTA_PUBLIC_KEY_HEX'),

    // Harus sama dengan device_type di manifest dan OTA_DEVICE_TYPE di firmware.
    'device_type' => env('OTA_DEVICE_TYPE', 'sanding-kecil'),

    // Harus sama dengan versi di manifest dan OTA_JENIS_VERSI di firmware.
    'jenis_versi' => env('OTA_JENIS_VERSI', 'versi'),
];
