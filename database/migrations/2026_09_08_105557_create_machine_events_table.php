<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('machine_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('machine_id')->constrained()->cascadeOnDelete();
            $table->enum('jenis', ['jaringan', 'sensor1', 'sensor2']);
            $table->enum('status', ['normal', 'gangguan']);
            $table->string('keterangan')->nullable();
            $table->timestamp('terjadi_pada');
            $table->timestamps();
            $table->index(['machine_id', 'terjadi_pada'], 'idx_events_mesin_waktu');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('machine_events');
    }
};
