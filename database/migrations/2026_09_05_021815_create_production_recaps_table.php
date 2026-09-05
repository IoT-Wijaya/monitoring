<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_recaps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('machine_id')->constrained('machines')->cascadeOnDelete();
            $table->date('tanggal');
            $table->unsignedTinyInteger('jam');
            $table->enum('shift', ['Shift Pagi', 'Shift Malam']);
            $table->unsignedBigInteger('sensor1_masuk')->default(0);
            $table->unsignedBigInteger('sensor2_keluar')->default(0);
            $table->timestamps();
            $table->index(['machine_id', 'tanggal', 'jam'], 'idx_recap_hourly_filter');
            $table->index('tanggal', 'idx_recap_hourly_tanggal');
            $table->unique(['machine_id', 'tanggal', 'jam'], 'uniq_recap_per_jam');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_recaps');
    }
};
