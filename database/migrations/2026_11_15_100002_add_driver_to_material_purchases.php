<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kiraya (کرایہ) ab tak sirf ek raqam thi, ye likha hi nahi jata tha ke
     * wo kis dale/truck wale ko gaya. Is liye kisi driver ka hisaab nikalna
     * mumkin nahi tha.
     *
     * driver_id usi vendors table ki taraf jata hai jahan type='driver' hoti
     * hai. Ye vendor_id se bilkul alag column hai, is liye material vs
     * sub-contractor ki taqseem (jo sirf vendor_id par lagti hai) ko ye
     * chhoota bhi nahi.
     *
     * Backfill ki zaroorat nahi: NULL ka saaf matlab "koi driver nahi".
     */
    public function up(): void
    {
        $mysql = in_array(Schema::getConnection()->getDriverName(), ['mysql', 'mariadb'], true);

        Schema::table('material_purchases', function (Blueprint $table) use ($mysql) {
            // after() sirf MySQL samajhta hai, SQLite usay chup chaap chhod
            // deti hai (column sab se aakhir me lagta hai, jo theek hai).
            $col = $table->foreignId('driver_id')->nullable();
            if ($mysql) {
                $col->after('vendor_id');
            }
            $col->constrained('vendors')->nullOnDelete();
        });
    }

    public function down(): void
    {
        // Foreign key aur column, dono EK hi Blueprint me. Ye zaroori hai:
        //   - MySQL par ye tarteeb se chalte hain, pehle key girti hai phir column.
        //   - SQLite par sirf dropColumn likhna nakaam hota hai, wo kehti hai
        //     'unknown column "driver_id" in foreign key definition'. Jab
        //     dropForeign saath ho to Laravel poori table nayi bana kar
        //     (temp table + copy + rename) dono cheezein ek saath hata deta hai.
        Schema::table('material_purchases', function (Blueprint $table) {
            $table->dropForeign(['driver_id']);
            $table->dropColumn('driver_id');
        });
    }
};
