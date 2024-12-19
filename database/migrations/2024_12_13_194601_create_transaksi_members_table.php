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
        Schema::create('transaksi_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('members');
            $table->foreignId('paket_member_id')->constrained('paket_members');
            $table->decimal('amount', 10, 2);
            $table->datetime('transaction_date');
            $table->string('payment_method');
            $table->string('payment_status');
            $table->unsignedBigInteger('processed_by')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('processed_by')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaksi_members');
    }
};
