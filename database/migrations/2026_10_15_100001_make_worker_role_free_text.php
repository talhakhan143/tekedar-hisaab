<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Wo roles jo pehle enum me the. down() ke waqt inhi tak wapas simatna hai. */
    private const LEGACY = ['mistri', 'mazdoor', 'electrician', 'plumber', 'painter', 'foreman', 'other'];

    /**
     * workers.role ek enum thi jis me saat purane roles likhe hue the, jab ke
     * role ki list Settings se badalne ke liye banayi gayi thi. Natija ye tha
     * ke Settings me koi naya role daalo to dropdown to us ko dikhata tha
     * magar save karte hi MySQL "Data truncated for column role" de deta,
     * yani worker add hota hi nahi tha.
     *
     * Ab ye saada string hai. Kaun sa role jaiz hai, ye app Settings dekh kar
     * khud tay karti hai.
     */
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE workers MODIFY role VARCHAR(50) NOT NULL DEFAULT 'mazdoor'");
        } else {
            // sqlite / others: enum waise bhi CHECK constraint ban kar baithti
            // hai, usay hata kar plain string bana dete hain.
            Schema::table('workers', function (Blueprint $table) {
                $table->string('role', 50)->default('mazdoor')->change();
            });
        }
    }

    public function down(): void
    {
        // Purani enum me sirf saat values aati hain, baqi sab 'other' ban jayen
        // warna column wapas enum ban hi nahi payega.
        DB::table('workers')->whereNotIn('role', self::LEGACY)->update(['role' => 'other']);

        if (in_array(Schema::getConnection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE workers MODIFY role ENUM('mistri', 'mazdoor', 'electrician', 'plumber', 'painter', 'foreman', 'other') NOT NULL DEFAULT 'mazdoor'");
        }
    }
};
