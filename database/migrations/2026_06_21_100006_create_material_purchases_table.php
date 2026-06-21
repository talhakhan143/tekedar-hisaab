<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('material_purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('vendor_id')->nullable()->constrained()->nullOnDelete();
            $table->date('date');
            $table->string('item_name');
            $table->decimal('qty', 14, 3)->default(0);
            $table->string('unit')->nullable();
            $table->unsignedBigInteger('rate_per_unit_paisa')->default(0);
            $table->unsignedBigInteger('amount_paisa')->default(0);
            $table->unsignedBigInteger('amount_paid_paisa')->default(0);
            $table->bigInteger('balance_due_paisa')->default(0); // amount - paid (udhaar)
            $table->decimal('wastage_qty', 14, 3)->nullable(); // actual wastage recorded
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('material_purchases');
    }
};
