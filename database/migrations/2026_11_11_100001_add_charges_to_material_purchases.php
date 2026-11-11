<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Naye chaar columns, isi tarteeb se. down() inhi ko wapas girata hai. */
    private const COLUMNS = ['goods_amount_paisa', 'freight_paisa', 'labour_paisa', 'other_charges_paisa'];

    /**
     * Thekedaar maal kharidta hai aur usi bill par kiraya (کرایہ) aur mazdoori
     * (مزدوری) bhi deta hai. Pehle sirf amount_paisa thi, is liye kiraya aur
     * mazdoori maal ki lagat me ghul jate the aur Estimate-vs-Actual me material
     * ka kharcha asal se zyada dikhta tha.
     *
     * Ab bill ke hissay alag likhay jate hain:
     *   goods_amount_paisa  = qty * rate (sirf maal)
     *   freight_paisa       = Kiraya (کرایہ)
     *   labour_paisa        = Mazdoori (مزدوری)
     *   other_charges_paisa = Deegar (دیگر)
     *
     * amount_paisa ka matlab waisa hi hai jaisa pehle tha: poora bill, yani
     * in chaaron ka jor. Is liye vendor ka udhaar (balance_due_paisa) ka
     * formula bilkul nahi badla.
     */
    public function up(): void
    {
        $mysql = in_array(Schema::getConnection()->getDriverName(), ['mysql', 'mariadb'], true);

        Schema::table('material_purchases', function (Blueprint $table) use ($mysql) {
            // goods ka koi default nahi: NULL ka matlab "purani row, abhi taqseem
            // nahi hui". Neeche ka backfill usay bhar deta hai.
            $goods   = $table->unsignedBigInteger('goods_amount_paisa')->nullable();
            $freight = $table->unsignedBigInteger('freight_paisa')->nullable()->default(0);
            $labour  = $table->unsignedBigInteger('labour_paisa')->nullable()->default(0);
            $other   = $table->unsignedBigInteger('other_charges_paisa')->nullable()->default(0);

            // after() sirf MySQL/MariaDB samajhti hai. SQLite (production) me
            // column hamesha aakhir me lagta hai, is liye wahan maangte hi nahi.
            if ($mysql) {
                $goods->after('amount_paisa');
                $freight->after('goods_amount_paisa');
                $labour->after('freight_paisa');
                $other->after('labour_paisa');
            }
        });

        // Backfill. Ye sirf saaf-suthrai nahi, laazmi hai: SUM() NULL ko chup
        // chaap chhod deti hai, to ek bhi un-backfilled row apni poori lagat
        // material cost se ghayab kar deti aur utna hi munafa jhoota barha deti.
        //
        // Jaan boojh kar na Eloquent, na deleted_at ki shart: soft-deleted rows
        // bhi backfill honi chahiye, warna un ko wapas laane par wahi ghayab
        // lagat wapas aa jati hai. MySQL aur SQLite dono isay waise hi chalati hain.
        DB::statement("UPDATE material_purchases SET goods_amount_paisa = amount_paisa, freight_paisa = 0, labour_paisa = 0, other_charges_paisa = 0 WHERE goods_amount_paisa IS NULL");
    }

    public function down(): void
    {
        Schema::table('material_purchases', function (Blueprint $table) {
            $table->dropColumn(self::COLUMNS);
        });
    }
};
