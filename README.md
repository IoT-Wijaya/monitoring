# Monitoring Produksi

Aplikasi monitoring produksi berbasis Laravel untuk memantau mesin dan counter
sensor secara real-time. Data perangkat diterima melalui MQTT, disimpan
sementara di cache aplikasi, ditampilkan melalui panel Filament, lalu diambil
snapshot-nya setiap jam untuk kebutuhan rekapitulasi produksi.

## Fitur

- Dashboard admin di `/admin` dengan autentikasi Filament.
- Widget monitoring real-time untuk setiap mesin (polling Livewire setiap 1
  detik).
- Informasi status online/offline, kecepatan Wi-Fi, SSID, counter Sensor 1 dan
  Sensor 2, serta indikator error sensor.
- Reset counter Sensor 1, Sensor 2, atau keduanya dari dashboard. Perintah
  reset dikirim kembali ke perangkat melalui MQTT.
- Master data mesin dan pengguna melalui Filament.
- Kontrol akses berbasis role dan permission menggunakan Filament Shield dan
  Spatie Laravel Permission.
- Rekapitulasi produksi per jam berdasarkan mesin, tanggal, dan shift.
- Log reset mesin dengan pengguna, target reset, dan nilai counter sebelum
  reset.
- Log event mesin yang dapat difilter berdasarkan mesin, jenis event, status,
  dan rentang tanggal.
- Integrasi opsional dengan InfluxDB untuk membaca data historis veneer,
  status perangkat, dan kecepatan Wi-Fi.

## Teknologi

- PHP 8.3+
- Laravel 13
- Filament 4
- Livewire
- MariaDB/MySQL atau SQLite untuk pengembangan
- MQTT 3.1 melalui `php-mqtt/laravel-client`
- InfluxDB 2.x melalui `influxdata/influxdb-client-php`
- Vite, Tailwind CSS, dan Node.js
- Docker Compose dengan PHP-FPM, Nginx, dan MariaDB

## Arsitektur singkat

```text
ESP32
  │ MQTT: pabrik/{slug}/{jenis}
  ▼
mqtt:subscribe ──► MachineCounterService ──► Cache
                                             │
                                             ├─► Dashboard Filament (Livewire)
                                             └─► rekap:snapshot-jam ──► production_recaps

Dashboard reset ──► MachineCounterService ──► MQTT: pabrik/{slug}/perintah
                                      └──────► machine_reset_logs
```

Listener MQTT hanya menerima slug yang sudah terdaftar pada tabel `machines`.
Pesan dari slug yang tidak dikenal akan diabaikan.

## Persyaratan

Untuk instalasi lokal:

- PHP 8.3 atau lebih baru dengan ekstensi PDO, Mbstring, XML, Intl, GD, dan
  Zip.
- Composer.
- Node.js LTS dan npm.
- Broker MQTT yang dapat dijangkau aplikasi.
- SQLite untuk setup sederhana, atau MySQL/MariaDB untuk lingkungan bersama.
- InfluxDB hanya diperlukan bila integrasi Influx digunakan.

## Instalasi lokal

1. Clone repository dan masuk ke direktori proyek:

   ```bash
   git clone https://github.com/IoT-Wijaya/monitoring.git
   cd monitoring
   ```

2. Pasang dependensi PHP dan JavaScript:

   ```bash
   composer install
   npm install
   ```

3. Buat file environment dan application key:

   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

   Pada Windows PowerShell, gunakan
   `Copy-Item .env.example .env` sebagai pengganti `cp`.

4. Pilih database. Untuk SQLite:

   ```bash
   New-Item database/database.sqlite -ItemType File
   ```

   Lalu pastikan `.env` berisi:

   ```dotenv
   DB_CONNECTION=sqlite
   ```

   Untuk MySQL/MariaDB, isi `DB_HOST`, `DB_PORT`, `DB_DATABASE`,
   `DB_USERNAME`, dan `DB_PASSWORD` sesuai server yang digunakan.

5. Tambahkan konfigurasi MQTT dan, bila diperlukan, InfluxDB ke `.env`:

   ```dotenv
   MQTT_HOST=127.0.0.1
   MQTT_PORT=1883
   MQTT_CLIENT_ID=monitoring-app
   MQTT_CLEAN_SESSION=true
   MQTT_ENABLE_LOGGING=true
   MQTT_AUTH_USERNAME=
   MQTT_AUTH_PASSWORD=
   MQTT_TLS_ENABLED=false

   INFLUXDB_URL=http://127.0.0.1:8086
   INFLUXDB_TOKEN=
   INFLUXDB_ORG=pabrik
   INFLUXDB_BUCKET=veneer_data
   ```

   Nama environment MQTT lain tersedia di
   [`config/mqtt-client.php`](config/mqtt-client.php), termasuk TLS,
   last-will, timeout, dan auto-reconnect.

6. Jalankan migrasi dan seeder:

   ```bash
   php artisan migrate --seed
   ```

   Seeder membuat pengguna contoh `test@example.com` dengan nama `Test User`.
   Seeder tidak menetapkan password maupun role; ubah atau buat pengguna
   administrator melalui Tinker sebelum login:

   ```bash
   php artisan tinker
   ```

   ```php
   $user = App\Models\User::where('email', 'test@example.com')->first();
   $user->password = 'ganti-dengan-password-kuat';
   $user->save();
   ```

7. Build asset frontend:

   ```bash
   npm run build
   ```

8. Jalankan aplikasi:

   ```bash
   php artisan serve
   ```

   Buka [http://localhost:8000](http://localhost:8000). Root `/` otomatis
   mengarahkan ke `/admin`.

## Menjalankan listener MQTT

Listener berjalan sebagai proses yang terus aktif dan harus dijalankan terpisah
dari web server:

```bash
php artisan mqtt:subscribe
```

Listener berlangganan ke topic `pabrik/#`. Format topic data yang diproses:

| Topic | Isi | Efek |
| --- | --- | --- |
| `pabrik/{slug}/jumlah` | Bilangan bulat | Counter Sensor 1 |
| `pabrik/{slug}/jumlah2` | Bilangan bulat | Counter Sensor 2 |
| `pabrik/{slug}/kecepatan` | Bilangan bulat | Kecepatan Wi-Fi |
| `pabrik/{slug}/ssid` | Teks | SSID perangkat |
| `pabrik/{slug}/s1_error` | `1` atau nilai lain | Status error Sensor 1 |
| `pabrik/{slug}/s2_error` | `1` atau nilai lain | Status error Sensor 2 |

Perangkat dianggap `ONLINE` jika pesan terakhir diterima maksimal 15 detik
sebelumnya. Saat reset dilakukan, aplikasi menerbitkan salah satu command berikut
ke `pabrik/{slug}/perintah`:

- `RESET_S1`
- `RESET_S2`
- `RESET_ALL`

## Rekapitulasi per jam

Scheduler mendefinisikan `rekap:snapshot-jam` untuk dijalankan setiap jam.
Command tersebut mengambil nilai counter saat ini untuk seluruh mesin dan
menyimpannya ke `production_recaps`; cache tidak di-nol-kan.

Jalankan manual:

```bash
php artisan rekap:snapshot-jam
```

Untuk scheduler berbasis cron, jalankan worker scheduler Laravel secara
periodik, misalnya setiap menit:

```cron
* * * * * cd /path/ke/monitoring && php artisan schedule:run >> /dev/null 2>&1
```

Shift ditentukan dari waktu server: pukul 06:00-17:59 adalah `Shift Pagi`,
sedangkan waktu lainnya adalah `Shift Malam`.

## Menjalankan frontend saat development

Untuk hot reload asset Vite:

```bash
npm run dev
```

Build produksi:

```bash
npm run build
```

## Docker Compose

Compose menyediakan service berikut:

- `app`: PHP-FPM Laravel.
- `nginx`: web server pada port `80`.
- `mysql`: MariaDB pada port `3306`.
- `mqtt-worker`: proses `php artisan mqtt:subscribe`.

Jalankan:

```bash
docker compose up -d --build
docker compose exec app php artisan migrate --seed
```

Aplikasi tersedia di [http://localhost](http://localhost). Konfigurasi Compose
menggunakan database `wijayapl_monitoring` dan kredensial contoh yang tertulis
di [`docker-compose.yml`](docker-compose.yml). Ganti semua password dan secret
tersebut sebelum deployment. Compose tidak menyediakan broker MQTT atau
InfluxDB, sehingga keduanya harus disiapkan di luar file Compose dan host-nya
harus dapat dijangkau dari container.

## Perintah penting

| Perintah | Kegunaan |
| --- | --- |
| `php artisan serve` | Menjalankan server development |
| `php artisan migrate --seed` | Migrasi database dan membuat data awal |
| `php artisan mqtt:subscribe` | Menjalankan listener MQTT |
| `php artisan rekap:snapshot-jam` | Menyimpan snapshot counter per jam |
| `php artisan schedule:work` | Menjalankan scheduler Laravel secara foreground |
| `php artisan optimize:clear` | Membersihkan cache konfigurasi, route, event, dan view |
| `composer test` | Menjalankan test suite PHPUnit melalui Artisan |
| `npm run dev` | Vite development server |
| `npm run build` | Build asset frontend |

## Struktur proyek

```text
app/
├── Console/Commands/       Command MQTT dan snapshot rekap
├── Filament/               Panel, resource, page, dan widget admin
├── Livewire/               Komponen filter dan tabel log/rekap
├── Models/                 Model mesin, event, reset, rekap, dan user
├── Policies/               Otorisasi resource dan role
├── Providers/              Konfigurasi Laravel dan panel Filament
└── Services/               Integrasi counter MQTT dan InfluxDB
config/                     Konfigurasi aplikasi, MQTT, permission, dan service
database/migrations/        Skema users, machines, rekap, log, job, cache, permission
database/seeders/           Data awal pengguna
docker/                     Dockerfile PHP dan konfigurasi Nginx
resources/views/            Template Blade untuk panel dan Livewire
routes/                     Route web dan scheduler/command
tests/                      Test Unit dan Feature PHPUnit
```

## Model data

- `machines`: nama mesin, `kode_esp32`, dan `slug` unik.
- `production_recaps`: satu snapshot per mesin, tanggal, dan jam; menyimpan
  nilai Sensor 1 dan Sensor 2 beserta shift.
- `machine_reset_logs`: audit setiap reset, target, pengguna, dan nilai sebelum
  reset.
- `machine_events`: event jaringan atau sensor dengan status normal/gangguan,
  keterangan, dan waktu kejadian.
- Tabel permission dari Spatie: role, permission, dan relasi model.

## Pengujian dan kualitas kode

Jalankan test yang tersedia:

```bash
php artisan test
```

Atau melalui script Composer:

```bash
composer test
```

Pemeriksaan format PHP menggunakan Laravel Pint:

```bash
./vendor/bin/pint --test
```

## Troubleshooting

- **Dashboard kosong atau mesin offline**: pastikan data mesin sudah ada,
  `MQTT_HOST` benar, dan `php artisan mqtt:subscribe` sedang berjalan.
- **Reset tidak terkirim**: periksa koneksi broker, kredensial MQTT, dan log
  aplikasi pada `storage/logs/laravel.log`.
- **Rekap tidak bertambah**: jalankan `php artisan rekap:snapshot-jam` secara
  manual untuk memastikan database dan cache dapat diakses, lalu aktifkan
  scheduler.
- **Perubahan `.env` tidak terbaca**: jalankan
  `php artisan optimize:clear`.
- **Gagal login**: pastikan pengguna memiliki password dan role/permission yang
  sesuai. Panel dilindungi middleware autentikasi Filament.

## Lisensi

Kode aplikasi ini mengikuti lisensi MIT dari skeleton Laravel yang digunakan.
