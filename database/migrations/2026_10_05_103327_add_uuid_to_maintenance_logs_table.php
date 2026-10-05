<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        // Tahap 1: tambah kolom (boleh kosong dulu karena data lama belum punya uuid)
        Schema::table('maintenance_logs', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->unique()->after('id');
        });

        // Tahap 2: isi uuid untuk data lama, per 500 baris agar memori aman
        DB::table('maintenance_logs')
            ->whereNull('uuid')
            ->orderBy('id')
            ->chunkById(500, function ($rows) {
                foreach ($rows as $row) {
                    DB::table('maintenance_logs')
                        ->where('id', $row->id)
                        ->update(['uuid' => (string) Str::uuid()]);
                }
            });

        // Tahap 3: setelah semua terisi, jadikan wajib
        Schema::table('maintenance_logs', function (Blueprint $table) {
            $table->uuid('uuid')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('maintenance_logs', function (Blueprint $table) {
            $table->dropColumn('uuid');
        });
    }
};
