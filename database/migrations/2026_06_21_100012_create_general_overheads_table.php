<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // monthly office overheads — NOT per project (reduce OVERALL profit)
    public function up(): void
    {
        Schema::create('general_overheads', function (Blueprint $table) {
            $table->id();
            $table->string('month', 7); // YYYY-MM
            $table->string('category');  // editable categories e.g. office_rent, salary_draw, marketing
            $table->unsignedBigInteger('amount_paisa')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('general_overheads');
    }
};
