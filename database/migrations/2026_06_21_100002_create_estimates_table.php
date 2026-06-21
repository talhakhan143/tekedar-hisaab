<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('estimates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->enum('category', ['material', 'labour', 'transport', 'equipment', 'subcontractor', 'utility', 'overhead', 'misc'])->default('material');
            $table->string('item_name');
            $table->string('unit')->nullable();
            $table->decimal('qty_estimated', 14, 3)->default(0);
            $table->unsignedBigInteger('rate_per_unit_paisa')->default(0);
            $table->unsignedBigInteger('amount_paisa')->default(0);
            $table->decimal('wastage_percent', 5, 2)->default(0); // material only
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('estimates');
    }
};
