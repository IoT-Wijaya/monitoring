<?php

namespace App\Services;

use InfluxDB2\Client;
use InfluxDB2\QueryApi;
use Illuminate\Support\Facades\Log;

class InfluxService
{
    protected Client $client;
    protected QueryApi $queryApi;
    protected string $bucket;

    public function __construct()
    {
        $this->bucket = config('services.influxdb.bucket');
        $this->client = new Client([
            "url"       => config('services.influxdb.url'),
            "token"     => config('services.influxdb.token'),
            "bucket"    => $this->bucket,
            "org"       => config('services.influxdb.org'),
            "precision" => \InfluxDB2\Model\WritePrecision::S,
            "timeout"   => 15,
        ]);

        $this->queryApi = $this->client->createQueryApi();
    }
    /**
     * Ambil angka hitungan veneer terakhir
     */
    public function getLastVeneerCount(): int
    {
        // Fleksibel mencari data berdasarkan kriteria topic MQTT
        $flux = 'from(bucket: "' . $this->bucket . '")
            |> range(start: -30d)
            |> filter(fn: (r) => r["topic"] == "pabrik/veneer/jumlah")
            |> last()';

        try {
            $tables = $this->queryApi->query($flux);
            foreach ($tables as $table) {
                foreach ($table->records as $record) {
                    $val = $record->getValue();
                    if ($val !== null) {
                        return (int) $val;
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error("InfluxDB Error (Veneer Count): " . $e->getMessage());
            throw $e; // Lempar exception agar terlihat di Tinker saat testing
        }

        return 0;
    }

    /**
     * Ambil status perangkat terakhir (1 = ONLINE, 0 = OFFLINE)
     */
    public function getLastDeviceStatus(): int
    {
        $flux = 'from(bucket: "' . $this->bucket . '")
            |> range(start: -30d)
            |> filter(fn: (r) => r["_measurement"] == "mqtt_consumer")
            |> filter(fn: (r) => r["topic"] == "pabrik/veneer/status")
            |> filter(fn: (r) => r["_field"] == "value")
            |> last()';

        try {
            $tables = $this->queryApi->query($flux);

            foreach ($tables as $table) {
                foreach ($table->records as $record) {
                    $val = $record->getValue();
                    if ($val !== null) {
                        return (int) $val;
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error("InfluxDB Error (Status): " . $e->getMessage());
        }

        return 0;
    }

    /**
     * Ambil kecepatan sinyal Wi-Fi (Mbps / dBm)
     */
    public function getLastWifiSpeed(): int
    {
        $flux = 'from(bucket: "' . $this->bucket . '")
            |> range(start: -30d)
            |> filter(fn: (r) => r["_measurement"] == "mqtt_consumer")
            |> filter(fn: (r) => r["topic"] == "pabrik/veneer/kecepatan")
            |> filter(fn: (r) => r["_field"] == "value")
            |> last()';

        try {
            $tables = $this->queryApi->query($flux);

            foreach ($tables as $table) {
                foreach ($table->records as $record) {
                    $val = $record->getValue();
                    if ($val !== null) {
                        return (int) $val;
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error("InfluxDB Error (Wifi Speed): " . $e->getMessage());
        }

        return 0;
    }

    public function __destruct()
    {
        $this->client->close();
    }
}
