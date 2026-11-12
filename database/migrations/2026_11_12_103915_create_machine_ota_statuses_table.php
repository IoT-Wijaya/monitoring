<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Hasil OTA terakhir per mesin. Satu baris per mesin (unik), sehingga laporan yang sama
     * (retained, terkirim ulang, atau diputar ulang saat mqtt:subscribe dimulai lagi)
     * tidak menggandakan data.
     */
    public function up(): void
    {
        Schema::create('machine_ota_statuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('machine_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('result', 32);
            $table->unsignedBigInteger('target_build');
            $table->unsignedBigInteger('previous_build');
            $table->unsignedBigInteger('running_build');
            $table->timestamp('reported_at'); // kapan server pertama kali menerima laporan ini
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('machine_ota_statuses');
    }
};
