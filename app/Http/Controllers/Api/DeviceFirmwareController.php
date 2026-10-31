<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ComputeFirmwareChecksum;
use App\Models\FirmwareRelease;
use App\Models\Machine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeviceFirmwareController extends Controller
{
    private const MAC_RULE = ['required', 'regex:/^([0-9A-Fa-f]{2}:){5}[0-9A-Fa-f]{2}$/'];

    private function findMachine(string $mac): ?Machine
    {
        return Machine::where('mac_address', strtoupper($mac))->first();
    }

    private function notRegistered(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Perangkat belum terdaftar. Isi mac_address pada data mesin.',
        ], 404);
    }

    public function latest(Request $request): JsonResponse
    {
        $data = $request->validate([
            'mac'             => self::MAC_RULE,
            'device'          => ['required', 'string', 'max:100'],
            'current_version' => ['nullable', 'string', 'max:50'],
        ]);

        $machine = $this->findMachine($data['mac']);
        if (!$machine) {
            return $this->notRegistered();
        }

        $machine->update([
            'device_type'     => $data['device'],
            'current_version' => $data['current_version'] ?? null,
            'last_seen_at'    => now(),
        ]);

        $latest = FirmwareRelease::active()
            ->where('device_type', $data['device'])
            ->whereNotNull('download_url')
            ->whereNotNull('checksum_sha256')
            ->latest('released_at')
            ->first();

        $current = ltrim($data['current_version'] ?? '0', 'vV');
        $updateAvailable = $latest
            && version_compare(ltrim($latest->version, 'vV'), $current, '>');

        // Sengaja tidak mengirim release notes: respons harus kecil untuk ESP32
        return response()->json([
            'success'          => true,
            'update_available' => (bool) $updateAvailable,
            'latest'           => $updateAvailable ? [
                'version'  => $latest->version,
                'checksum' => $latest->checksum_sha256,
            ] : null,
        ]);
    }

    public function download(Request $request)
    {
        $data = $request->validate([
            'mac'     => self::MAC_RULE,
            'device'  => ['required', 'string', 'max:100'],
            'version' => ['required', 'string', 'max:50'],
        ]);

        if (!$this->findMachine($data['mac'])) {
            return $this->notRegistered();
        }

        $release = FirmwareRelease::active()
            ->where('device_type', $data['device'])
            ->where('version', $data['version'])
            ->whereNotNull('checksum_sha256')
            ->first();

        if (!$release) {
            return response()->json(['success' => false, 'message' => 'Firmware tidak tersedia.'], 404);
        }

        $path = $release->localPath();

        if (!is_file($path)) {
            // File cache hilang: siapkan ulang di background
            ComputeFirmwareChecksum::dispatch($release->id);
            return response()->json(['success' => false, 'message' => 'File sedang disiapkan, coba lagi.'], 503);
        }

        return response()->download($path, basename($path), [
            'Content-Type'      => 'application/octet-stream',
            'X-Checksum-SHA256' => $release->checksum_sha256,
        ]);
    }

    public function report(Request $request): JsonResponse
    {
        $data = $request->validate([
            'mac'     => self::MAC_RULE,
            'version' => ['required', 'string', 'max:50'],
            'status'  => ['required', 'in:success,failed'],
            'message' => ['nullable', 'string', 'max:1000'],
        ]);

        $machine = $this->findMachine($data['mac']);
        if (!$machine) {
            return $this->notRegistered();
        }

        $machine->update([
            'current_version'  => $data['status'] === 'success' ? $data['version'] : $machine->current_version,
            'last_ota_status'  => $data['status'],
            'last_ota_message' => $data['message'] ?? null,
            'last_ota_at'      => now(),
            'last_seen_at'     => now(),
        ]);

        return response()->json(['success' => true]);
    }
}
