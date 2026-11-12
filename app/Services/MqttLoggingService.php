<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Throwable;

class MqttLoggingService
{
    /**
     * Melaporkan exception lengkap ke Sentry.
     */
    public function reportException(
        Throwable $e,
        string $errorType,
        array $context = []
    ): void {
        $this->sendToSentry(function ($scope) use ($e, $errorType, $context) {
            $scope->setLevel(\Sentry\Severity::error());

            $scope->setTag('service', 'mqtt-worker');
            $scope->setTag('mqtt_error_type', $errorType);
            $scope->setTag('environment', app()->environment());

            $scope->setContext('mqtt_detail', $context);

            $scope->setContext('exception_detail', [
                'class' => get_class($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'previous_exception' => $e->getPrevious()
                    ? [
                        'class' => get_class($e->getPrevious()),
                        'message' => $e->getPrevious()->getMessage(),
                        'file' => $e->getPrevious()->getFile(),
                        'line' => $e->getPrevious()->getLine(),
                    ]
                    : null,
            ]);

            \Sentry\captureException($e);
        });
    }

    /**
     * Melaporkan warning non-exception ke Sentry.
     */
    public function reportWarning(
        string $message,
        string $warningType,
        array $context = []
    ): void {
        $this->sendToSentry(function ($scope) use ($message, $warningType, $context) {
            $scope->setLevel(\Sentry\Severity::warning());

            $scope->setTag('service', 'mqtt-worker');
            $scope->setTag('mqtt_error_type', $warningType);
            $scope->setTag('environment', app()->environment());

            $scope->setContext('mqtt_detail', $context);

            \Sentry\captureMessage($message);
        });
    }

    /**
     * Pengiriman Sentry dengan perlindungan agar tidak
     * mengganggu proses utama MQTT.
     */
    private function sendToSentry(callable $callback): void
    {
        if (! app()->bound('sentry')) {
            return;
        }

        try {
            \Sentry\withScope($callback);

            \Sentry\SentrySdk::getCurrentHub()
                ->getClient()?->flush(2);
        } catch (Throwable $e) {
            Log::warning('[MQTT SENTRY] Gagal mengirim laporan', [
                'error' => $e->getMessage(),
                'exception' => get_class($e),
            ]);
        }
    }
}
