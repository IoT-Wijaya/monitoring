<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ComputeFirmwareChecksum;
use App\Models\FirmwareRelease;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class WebhookController extends Controller
{
    public function handleGitHubRelease(Request $request): JsonResponse
    {
        // 1. Verifikasi signature
        $secret    = (string) config('services.github.webhook_secret');
        $signature = (string) $request->header('X-Hub-Signature-256', '');
        $expected  = 'sha256=' . hash_hmac('sha256', $request->getContent(), $secret);

        // Jika secret kosong di server, tolak semua (lebih aman)
        if ($secret === '' || !hash_equals($expected, $signature)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized: Invalid signature',
            ], Response::HTTP_UNAUTHORIZED);
        }

        // 2. Balas "pong" untuk event ping (dikirim GitHub saat webhook pertama dibuat)
        $event = $request->header('X-GitHub-Event');

        if ($event === 'ping') {
            return response()->json(['success' => true, 'message' => 'pong']);
        }

        // 3. Hanya proses release yang baru dipublikasikan
        if ($event !== 'release' || $request->input('action') !== 'published') {
            return $this->ignored('Event diterima, tetapi diabaikan.');
        }

        $release = $request->input('release', []);

        // 4. Abaikan draft dan prerelease
        if (($release['draft'] ?? false) || ($release['prerelease'] ?? false)) {
            return $this->ignored('Draft/prerelease diabaikan.');
        }

        $tagName = $release['tag_name'] ?? null;

        // 5. Cari asset .bin (bukan sekadar asset pertama)
        $binAsset = collect($release['assets'] ?? [])->first(
            fn($asset) => str_ends_with(strtolower($asset['name'] ?? ''), '.bin')
        );

        if (!$tagName || !$binAsset) {
            return $this->ignored('Rilis tidak punya tag atau file .bin.');
        }

        // 6. Simpan. Checksum dikosongkan dulu karena file mungkin berubah
        $firmware = FirmwareRelease::updateOrCreate(
            ['version' => $tagName],
            [
                'download_url'    => $binAsset['browser_download_url'],
                'release_notes'   => $release['body'] ?? null,
                'checksum_sha256' => null,
                'is_active'       => true,
                'released_at'     => now(),
            ]
        );

        // 7. Hitung checksum di background
        ComputeFirmwareChecksum::dispatch($firmware->id);

        return response()->json([
            'success' => true,
            'message' => "Firmware versi {$tagName} diterima, checksum sedang dihitung.",
        ]);
    }

    private function ignored(string $message): JsonResponse
    {
        // Tetap 200 agar GitHub tidak menandai webhook sebagai gagal
        return response()->json(['success' => true, 'message' => $message]);
    }
}
