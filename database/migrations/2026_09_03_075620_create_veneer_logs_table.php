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
        Schema::create('veneer_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('jumlah');
            $table->integer('kecepatan_mbps')->nullable();
            $table->string('status_sinyal')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('veneer_logs');
    }
};
