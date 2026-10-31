<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureDeviceApiKey
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $master   = (string) config('services.device.master_secret');
        $mac      = strtoupper((string) $request->input('mac', ''));
        $provided = (string) $request->header('X-Device-Key', '');

        // Key yang benar untuk MAC ini dihitung ulang dari master secret
        $expected = ($master !== '' && $mac !== '')
            ? hash_hmac('sha256', $mac, $master)
            : '';

        if ($expected === '' || !hash_equals($expected, $provided)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized: Invalid device key',
            ], Response::HTTP_UNAUTHORIZED);
        }

        return $next($request);
    }
}
