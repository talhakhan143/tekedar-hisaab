<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->unsignedBigInteger('gross_amount_paisa')->default(0);
            $table->unsignedBigInteger('retention_held_paisa')->default(0);
            $table->unsignedBigInteger('net_received_paisa')->default(0);
            $table->enum('payment_method', ['cash', 'bank', 'cheque', 'online'])->default('cash');
            $table->string('reference')->nullable(); // bill_no / cheque no
            $table->boolean('is_mobilization')->default(false); // mobilization advance flag
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_payments');
    }
};
