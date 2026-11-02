<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class FirmwareReleaseService
{
    /**
     * Versi firmware terbaru untuk satu kunci perangkat (mis. "hmi"),
     * mis. "v2026.11.1.1". Null bila tidak diketahui.
     */
    public function versiTerbaru(string $key): ?string
    {
        // Cadangan manual di config/machines.php, dipakai bila GitHub tidak bisa dihubungi.
        $cadangan = config("machines.latest_firmware.{$key}");
        $repo = config('services.github.repo');

        // Repo belum diatur: pakai nilai manual seperti dulu.
        if (! $repo) {
            return $cadangan;
        }

        // Hasil (termasuk cadangan saat gagal) disimpan 5 menit, sehingga
        // GitHub tidak ditembak tiap polling widget.
        $versi = Cache::remember("firmware_terbaru_{$key}", 300, function () use ($repo, $cadangan) {
            try {
                $request = Http::timeout(5)
                    ->acceptJson()
                    ->withHeaders(['X-GitHub-Api-Version' => '2022-11-28']);

                if ($token = config('services.github.token')) {
                    $request = $request->withToken($token);
                }

                $res = $request->get("https://api.github.com/repos/{$repo}/releases/latest");

                if ($res->successful()) {
                    // Ambil bagian versi saja dari nama tag, mis. "input-display-v2026.11.1.1".
                    if (preg_match('/v?\d+(?:\.\d+)+/i', (string) $res->json('tag_name'), $m)) {
                        return $m[0];
                    }
                }
            } catch (\Throwable $e) {
                report($e);
            }

            return $cadangan ?? '';   // '' = tidak diketahui (null tidak bisa di-cache)
        });

        return $versi !== '' ? $versi : null;
    }
}
