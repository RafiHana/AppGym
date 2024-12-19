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
        Schema::create('sesi_gyms', function (Blueprint $table) {
            // Identifikasi Utama
            $table->id();
            $table->unsignedBigInteger('member_id');
            $table->foreign('member_id')
                  ->references('id')
                  ->on('members')
                  ->onDelete('cascade'); // Hapus sesi jika member dihapus
            $table->timestamp('check_in_time')->nullable(); // Waktu check-in
            $table->timestamp('check_out_time')->nullable(); // Waktu check-out
            $table->integer('total_duration')->default(0); // Durasi dalam menit
            $table->dateTime('session_date')->nullable(); // Tanggal sesi
            $table->enum('status', [
                'active',      // Sedang berlangsung
                'completed',   // Sudah selesai
                'interrupted', // Terputus
                'pending'      // Menunggu konfirmasi
            ])->default('pending');
            $table->string('check_in_location')->nullable(); // Lokasi check-in
            $table->string('device_info')->nullable(); // Informasi perangkat
            $table->text('notes')->nullable(); // Catatan tambahan
            $table->unsignedBigInteger('verified_by')->nullable(); // Admin yang memverifikasi
            $table->foreign('verified_by')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade'); // Masih terjadi error di verifide_by
            $table->json('zone_times')->nullable(); // Waktu di berbagai zona latihan
            $table->timestamps(); // created_at dan updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sesi_gyms');
    }
};
