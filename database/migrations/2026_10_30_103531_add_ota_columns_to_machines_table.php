<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1) Rename hanya jika kolom lama masih ada (aman dijalankan ulang)
        if (Schema::hasColumn('machines', 'kode_esp32')) {
            Schema::table('machines', function (Blueprint $table) {
                $table->renameColumn('kode_esp32', 'mac_address');
            });
        }

        // 2) Ubah struktur dulu: boleh NULL
        Schema::table('machines', function (Blueprint $table) {
            $table->string('mac_address', 255)->nullable()->change();
        });

        // 3) Baru kosongkan data acak lama
        DB::table('machines')->update(['mac_address' => null]);

        // 4) Tambahkan kolom OTA
        Schema::table('machines', function (Blueprint $table) {
            $table->string('device_type', 100)->nullable()->after('slug');
            $table->string('current_version', 50)->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->string('last_ota_status', 20)->nullable();
            $table->text('last_ota_message')->nullable();
            $table->timestamp('last_ota_at')->nullable();

            $table->index('device_type');
        });
    }

    public function down(): void
    {
        Schema::table('machines', function (Blueprint $table) {
            $table->dropIndex(['device_type']);
            $table->dropColumn([
                'device_type',
                'current_version',
                'last_seen_at',
                'last_ota_status',
                'last_ota_message',
                'last_ota_at',
            ]);
        });

        Schema::table('machines', function (Blueprint $table) {
            $table->renameColumn('mac_address', 'kode_esp32');
        });
    }
};
