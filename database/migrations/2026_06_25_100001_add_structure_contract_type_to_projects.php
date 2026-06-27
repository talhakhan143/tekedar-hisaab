<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add a third contract type — "structure" — alongside the existing
     * grey_structure and full_finished. Client now picks one of three:
     * Structure / Gray Structure / Full Furnish.
     */
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE projects MODIFY contract_type ENUM('structure', 'grey_structure', 'full_finished') NOT NULL DEFAULT 'full_finished'");
        } else {
            // sqlite / others: drop the old enum CHECK by rebuilding the column
            // as a plain string. App-level validation enforces the allowed set.
            Schema::table('projects', function (Blueprint $table) {
                $table->string('contract_type')->default('full_finished')->change();
            });
        }
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        // Collapse any 'structure' rows back to grey_structure before shrinking.
        DB::table('projects')->where('contract_type', 'structure')->update(['contract_type' => 'grey_structure']);

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE projects MODIFY contract_type ENUM('grey_structure', 'full_finished') NOT NULL DEFAULT 'full_finished'");
        }
    }
};
