<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('machines', function (Blueprint $table) {
            $table->id();
            $table->string('nama');                 // "Mesin Sanding", "Mesin Cutting CNC", dst
            $table->string('kode_esp32')->unique();  // "ESP32-Sanding" -> dipakai device utk auth/identifikasi
            $table->string('slug')->unique();        // "mesin-sanding" -> aman dipakai di URL/route
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('machines');
    }
};
