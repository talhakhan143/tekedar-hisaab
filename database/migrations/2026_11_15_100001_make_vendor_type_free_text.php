<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Wo types jo pehle enum me the. down() ke waqt inhi tak wapas simatna hai. */
    private const LEGACY = ['supplier', 'subcontractor'];

    /**
     * vendors.type ek enum thi jis me sirf do values likhi hui thin:
     * supplier aur subcontractor. Ab maalik ko 'driver' (ڈرائیور) bhi chahiye,
     * taake kiraya (کرایہ) kis dale/truck wale ko diya, ye record ho sake.
     *
     * Enum me nayi value daalna khaamoshi se fail hota hai: MySQL "Data
     * truncated for column type" de kar row hi nahi banata. Bilkul yehi bug
     * workers.role par pehle pesh aa chuka hai (dekho migration
     * 2026_10_15_100001_make_worker_role_free_text.php) aur us ne kai din
     * khaye the, kyunke dropdown me value dikhti thi magar save nahi hoti thi.
     *
     * Is liye ab ye saada string hai. Kaun sa type jaiz hai, ye app khud
     * Vendor::TYPES dekh kar tay karti hai.
     */
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE vendors MODIFY type VARCHAR(50) NOT NULL DEFAULT 'supplier'");
        } else {
            // sqlite / others: enum waise bhi CHECK constraint ban kar baithti
            // hai, usay hata kar plain string bana dete hain.
            Schema::table('vendors', function (Blueprint $table) {
                $table->string('type', 50)->default('supplier')->change();
            });
        }
    }

    public function down(): void
    {
        // Purani enum me sirf do values aati hain, baqi sab 'supplier' ban
        // jayen warna column wapas enum ban hi nahi payega.
        DB::table('vendors')->whereNotIn('type', self::LEGACY)->update(['type' => 'supplier']);

        if (in_array(Schema::getConnection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE vendors MODIFY type ENUM('supplier', 'subcontractor') NOT NULL DEFAULT 'supplier'");
        }
    }
};
