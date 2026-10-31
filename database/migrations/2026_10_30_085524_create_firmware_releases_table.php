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
        Schema::create('firmware_releases', function (Blueprint $table) {
            $table->id();
            $table->string('device_type', 100);
            $table->string('version', 50);                      // tanpa ->unique()
            $table->text('download_url')->nullable();
            $table->string('checksum_sha256', 64)->nullable();
            $table->text('release_notes')->nullable();
            $table->boolean('is_active')->default(false);       // aman secara default
            $table->timestamp('released_at')->nullable();
            $table->timestamps();

            // Satu versi hanya sekali per jenis perangkat
            $table->unique(['device_type', 'version']);

            // Mempercepat query "firmware aktif terbaru per jenis perangkat"
            $table->index(['device_type', 'is_active', 'released_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('firmware_releases');
    }
};
