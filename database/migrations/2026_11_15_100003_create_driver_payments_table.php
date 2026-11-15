<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Driver ko kiraya dene ka khaata.
 *
 * Bilkul wage_payments ki tarah: material_purchases.freight_paisa batata hai
 * ke driver ka kitna banta hai, aur ye table batata hai ke us me se kitna diya
 * gaya. Baqi = kul kiraya - jo diya.
 *
 * Yahan ek zaroori baat hai: jab purchase par driver chuna ho to us ka kiraya
 * supplier ke balance_due_paisa me se nikal diya jata hai (MaterialPurchase ke
 * saving hook me). Warna wahi raqam do dafa "dena" me ginti: ek dafa supplier
 * ka udhaar, ek dafa driver ka. Jis purchase par driver na ho, us ka kiraya
 * pehle ki tarah supplier ke bill me hi rehta hai.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('driver_payments', function (Blueprint $table) {
            $table->id();
            // Driver bhi vendors table me rehta hai (type = driver), is liye
            // alag se koi doosri logon ki list nahi banani parti.
            $table->foreignId('driver_id')->constrained('vendors')->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->date('date');
            $table->unsignedBigInteger('amount_paisa')->default(0);
            $table->string('payment_method')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['driver_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('driver_payments');
    }
};
