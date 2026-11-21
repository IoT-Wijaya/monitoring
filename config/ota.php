<?php

return [
    // Folder induk rilis di server. Tiap mesin punya subfolder sendiri berisi manifest.signed
    // dan firmware.bin: <manifest_root>/<folder>/manifest.signed. Di VPS ini folder yang sama
    // yang disajikan nginx di /fw/<folder>/ (dan dipakai CI: /srv/firmware/<folder>/).
    'manifest_root' => env('OTA_MANIFEST_ROOT', '/var/www/firmware'),

    // Mesin yang boleh di-update lewat OTA, dikunci dengan slug mesin di Laravel (segmen kedua
    // topik MQTT: pabrik/<slug>/...). Menambah mesin = menambah satu baris di sini.
    //   device_type : harus sama dengan device_type di manifest, OTA_DEVICE_TYPE di firmware,
    //                 dan FW_DEVICE_TYPE di tools/ci_rilis.py.
    //   folder      : nama subfolder rilis; harus sama dengan OTA_DIR di firmware (/fw/<folder>/).
    // Mesin yang tidak tercantum di sini tidak mendapat tombol Update.
    'mesin' => [
        'sanding' => ['device_type' => 'sanding-kecil', 'folder' => 'sanding-kecil'],
        'sanding-besar' => ['device_type' => 'sanding-besar', 'folder' => 'sanding-besar'],
    ],

    // Kunci publik Ed25519 (32 byte, heksadesimal 64 karakter): pasangan kunci rahasia
    // di GitHub Secret OTA_SIGNING_KEY, sama dengan include/ota_public_key.h di firmware.
    'public_key_hex' => env('OTA_PUBLIC_KEY_HEX'),

    // Harus sama dengan versi di manifest dan OTA_JENIS_VERSI di firmware.
    'jenis_versi' => env('OTA_JENIS_VERSI', 'versi'),
];
