<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_recaps', function (Blueprint $table) {
            $table->unsignedInteger('produksi_s1')->default(0)->after('sensor2_keluar');
            $table->unsignedInteger('produksi_s2')->default(0)->after('produksi_s1');
        });
    }

    public function down(): void
    {
        Schema::table('production_recaps', function (Blueprint $table) {
            $table->dropColumn(['produksi_s1', 'produksi_s2']);
        });
    }
};
