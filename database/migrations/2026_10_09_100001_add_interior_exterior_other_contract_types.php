<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Values that exist only after this migration runs. */
    private const ADDED = ['interior', 'exterior', 'other'];

    /**
     * Widen contract_type with Interior / Exterior / Other, so the client can
     * book finishing-only jobs alongside the three structural types.
     */
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE projects MODIFY contract_type ENUM('structure', 'grey_structure', 'full_finished', 'interior', 'exterior', 'other') NOT NULL DEFAULT 'full_finished'");
        } else {
            // sqlite / others: keep it a plain string, no CHECK constraint to
            // fight with. App-level validation enforces the allowed set.
            Schema::table('projects', function (Blueprint $table) {
                $table->string('contract_type')->default('full_finished')->change();
            });
        }
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        // Rows on a new type would not fit the old enum, so fold them back first.
        DB::table('projects')->whereIn('contract_type', self::ADDED)->update(['contract_type' => 'full_finished']);

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE projects MODIFY contract_type ENUM('structure', 'grey_structure', 'full_finished') NOT NULL DEFAULT 'full_finished'");
        }
    }
};
