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
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->string('endpoint');
            $table->string('method');
            $table->json('request_data')->nullable();
            $table->integer('response_status');
            $table->json('response_data')->nullable();
            $table->string('device_id')->nullable();
            $table->unsignedBigInteger('api_key_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
