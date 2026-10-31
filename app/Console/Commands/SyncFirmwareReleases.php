<?php

namespace App\Console\Commands;

use App\Jobs\ComputeFirmwareChecksum;
use App\Models\FirmwareRelease;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

#[Signature('firmware:sync {--limit=30 : Jumlah release terbaru yang dicek}')]
#[Description('Ambil release firmware terbaru dari GitHub')]
class SyncFirmwareReleases extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $repo = config('services.github.repo');

        if (!$repo) {
            $this->error('GITHUB_REPO belum diisi di .env');
            return self::FAILURE;
        }

        $request = Http::acceptJson()->timeout(20);

        if ($token = config('services.github.token')) {
            $request = $request->withToken($token);
        }

        $response = $request->get("https://api.github.com/repos/{$repo}/releases", [
            'per_page' => (int) $this->option('limit'),
        ]);

        if ($response->failed()) {
            $this->error("GitHub API gagal: HTTP {$response->status()}");
            return self::FAILURE;
        }

        $new = 0;

        foreach ($response->json() as $release) {
            // Lewati draft dan prerelease
            if (($release['draft'] ?? false) || ($release['prerelease'] ?? false)) {
                continue;
            }

            $tag = (string) ($release['tag_name'] ?? '');

            if (!preg_match('/^(?<device>[a-z0-9-]+)-(?<version>v\d[\d.]*)$/i', $tag, $m)) {
                continue;
            }

            $asset = collect($release['assets'] ?? [])
                ->first(fn($a) => ($a['name'] ?? '') === "{$tag}.bin");

            if (!$asset) {
                continue;
            }

            // Sudah ada: jangan diutak-atik (menjaga is_active dan checksum)
            if (FirmwareRelease::where('device_type', $m['device'])
                ->where('version', $m['version'])->exists()
            ) {
                continue;
            }

            $firmware = FirmwareRelease::create([
                'device_type'     => $m['device'],
                'version'         => $m['version'],
                'download_url'    => $asset['browser_download_url'],
                'release_notes'   => $release['body'] ?? null,
                'checksum_sha256' => null,
                'is_active'       => false, // gerbang persetujuan
                'released_at'     => $release['published_at'] ?? now(),
            ]);

            ComputeFirmwareChecksum::dispatch($firmware->id);
            $new++;
            $this->info("Baru: {$m['device']} {$m['version']}");
        }

        $this->info("Selesai. {$new} release baru.");

        return self::SUCCESS;
    }
}
