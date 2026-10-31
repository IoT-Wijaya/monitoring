<?php

namespace App\Jobs;

use App\Models\FirmwareRelease;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ComputeFirmwareChecksum implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;      // ulangi maksimal 3x jika gagal
    public int $timeout = 120;  // batas waktu 2 menit

    /**
     * Create a new job instance.
     */
    public function __construct(public int $firmwareReleaseId)
    {
        //
    }

    // Jeda antar percobaan ulang: 10 detik, 30 detik, 60 detik
    public function backoff(): array
    {
        return [10, 30, 60];
    }


    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $release = FirmwareRelease::find($this->firmwareReleaseId);

        if (!$release || !$release->download_url) {
            return;
        }

        $file = $release->localPath();
        $tmp  = $file . '.part';

        if (!is_dir(dirname($file))) {
            mkdir(dirname($file), 0755, true);
        }

        try {
            Http::timeout(100)
                ->withOptions(['sink' => $tmp])
                ->get($release->download_url)
                ->throw();

            if (is_file($file)) {
                unlink($file);
            }
            rename($tmp, $file);

            $release->update(['checksum_sha256' => hash_file('sha256', $file)]);
        } finally {
            @unlink($tmp);
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::error('Gagal menyiapkan firmware', [
            'release_id' => $this->firmwareReleaseId,
            'error'      => $e->getMessage(),
        ]);
    }
}
