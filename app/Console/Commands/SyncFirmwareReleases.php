<?php

namespace App\Console\Commands;

use App\Jobs\ComputeFirmwareChecksum;
use App\Models\FirmwareRelease;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class SyncFirmwareReleases extends Command
{
    protected $signature = 'firmware:sync';
    protected $description = 'Salin rilis firmware dari GitHub ke tabel firmware_releases';

    public function handle(): int
    {
        $repo = config('services.github.repo');
        $prefixes = (array) config('services.github.tag_prefixes', []);

        if (! $repo || $prefixes === []) {
            $this->error('GITHUB_REPO atau GITHUB_TAG_PREFIX belum diisi.');
            return self::FAILURE;
        }

        // Awalan terpanjang dicek lebih dulu, supaya "counter-sanding-besar"
        // tidak salah masuk ke "counter-sanding".
        usort($prefixes, fn($a, $b) => strlen($b) <=> strlen($a));

        $request = Http::timeout(10)->acceptJson()
            ->withHeaders(['X-GitHub-Api-Version' => '2022-11-28']);

        if ($token = config('services.github.token')) {
            $request = $request->withToken($token);
        }

        $res = $request->get("https://api.github.com/repos/{$repo}/releases", ['per_page' => 50]);

        if (! $res->successful()) {
            $this->error('GitHub menjawab HTTP ' . $res->status());
            return self::FAILURE;
        }

        foreach ($res->json() as $rilis) {
            if (($rilis['draft'] ?? false) || ($rilis['prerelease'] ?? false)) {
                continue;
            }

            $tag = $rilis['tag_name'] ?? '';

            // Cari awalan yang cocok (yang terpanjang menang).
            $deviceType = collect($prefixes)->first(
                fn($p) => str_starts_with(strtolower($tag), strtolower($p))
            );

            if (! $deviceType || ! preg_match('/v?\d+(?:\.\d+)+/i', $tag, $m)) {
                continue;
            }

            $bin = collect($rilis['assets'] ?? [])
                ->first(fn($a) => str_ends_with(strtolower($a['name'] ?? ''), '.bin'));

            if (! $bin) {
                $this->warn("{$tag}: tidak ada file .bin, dilewati.");
                continue;
            }

            $firmware = FirmwareRelease::updateOrCreate(
                ['device_type' => $deviceType, 'version' => $m[0]],
                [
                    'download_url' => $bin['browser_download_url'],
                    'released_at'  => $rilis['published_at'] ?? now(),
                ]
            );

            // Hanya rilis yang baru dibuat yang diaktifkan otomatis.
            // Rilis lama yang sengaja dinonaktifkan manual tidak diubah.
            if ($firmware->wasRecentlyCreated) {
                $firmware->update(['is_active' => true]);
            }

            if (blank($firmware->checksum_sha256)) {
                ComputeFirmwareChecksum::dispatch($firmware->id);
                $this->info("[{$deviceType}] {$m[0]}: baru, checksum diproses.");
            }
        }

        return self::SUCCESS;
    }
}
