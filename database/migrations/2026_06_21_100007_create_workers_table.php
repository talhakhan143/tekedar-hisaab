<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('role', ['mistri', 'mazdoor', 'electrician', 'plumber', 'painter', 'foreman', 'other'])->default('mazdoor');
            $table->enum('wage_type', ['daily', 'monthly', 'contract_piece'])->default('daily');
            $table->unsignedBigInteger('default_wage_paisa')->default(0); // per day / per month / per piece
            $table->string('phone')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workers');
    }
};
