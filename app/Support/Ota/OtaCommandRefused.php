<?php

namespace App\Support\Ota;

use RuntimeException;

/** Perintah OTA ditolak sebelum diterbitkan. Pesannya aman ditampilkan ke operator. */
final class OtaCommandRefused extends RuntimeException {}
