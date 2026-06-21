<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('client_name')->nullable();
            $table->string('client_phone')->nullable();
            $table->string('client_address')->nullable();

            $table->enum('contract_type', ['grey_structure', 'full_finished'])->default('full_finished');
            $table->enum('pricing_mode', ['per_sqft', 'lump_sum'])->default('per_sqft');

            $table->decimal('covered_area_sqft', 12, 2)->nullable();
            $table->unsignedBigInteger('rate_per_sqft_paisa')->nullable();
            $table->unsignedBigInteger('contract_value_paisa')->default(0);

            $table->decimal('retention_percent', 5, 2)->default(0);
            $table->decimal('completion_percent', 5, 2)->default(0); // manual override

            $table->date('start_date')->nullable();
            $table->date('expected_end_date')->nullable();
            $table->date('actual_end_date')->nullable();

            $table->enum('status', ['quoted', 'active', 'on_hold', 'completed', 'closed'])->default('quoted');
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
