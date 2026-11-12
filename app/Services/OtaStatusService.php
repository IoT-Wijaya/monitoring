<?php

namespace App\Services;

use App\Models\Machine;
use App\Models\MachineOtaStatus;
use App\Support\Ota\InvalidOtaStatusException;
use App\Support\Ota\OtaStatusParser;

/**
 * Menyimpan hasil OTA terakhir yang dilaporkan perangkat lewat topik ota_status.
 *
 * Perangkat menjamin "minimal sekali" dan pesannya retained, jadi laporan yang sama bisa
 * datang berkali-kali. Penyimpanannya idempoten: laporan identik tidak mengubah apa pun.
 */
class OtaStatusService
{
    public function __construct(private MqttLoggingService $logger) {}

    /** Mengembalikan true bila laporan sah dan mesinnya dikenal (baru atau sama dengan yang tersimpan). */
    public function catat(string $slug, string $payload): bool
    {
        try {
            $status = OtaStatusParser::parse($payload);
        } catch (InvalidOtaStatusException $e) {
            $this->logger->reportWarning(
                "Laporan OTA tidak valid dari [{$slug}]: {$e->getMessage()}",
                'ota_status_invalid',
                [
                    'machine_slug' => $slug,
                    'payload' => mb_substr($payload, 0, 512),
                    'timestamp' => now()->toIso8601String(),
                ]
            );

            return false;
        }

        $machine = Machine::where('slug', $slug)->first();
        if ($machine === null) {
            return false;
        }

        $baris = MachineOtaStatus::firstOrNew(['machine_id' => $machine->id]);
        $baris->fill([
            'result' => $status->result,
            'target_build' => $status->targetBuild,
            'previous_build' => $status->previousBuild,
            'running_build' => $status->runningBuild,
        ]);

        // Laporan identik (retained, terkirim ulang, diputar ulang) tidak mengubah apa pun.
        if (! $baris->exists || $baris->isDirty()) {
            $baris->reported_at = now();
            $baris->save();
        }

        return true;
    }
}
