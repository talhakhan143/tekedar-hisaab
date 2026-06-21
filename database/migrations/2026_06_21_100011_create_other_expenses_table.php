<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('other_expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete(); // null = general/office
            $table->date('date');
            $table->enum('category', ['transport', 'equipment_rental', 'fuel', 'utility', 'rent', 'tools', 'food_chai', 'permits_govt', 'bank_charges', 'misc'])->default('misc');
            $table->string('description')->nullable();
            $table->unsignedBigInteger('amount_paisa')->default(0);
            $table->string('paid_to')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('other_expenses');
    }
};
