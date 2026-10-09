<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Retention ka concept app se nikal dia gaya hai (owner ke thekon me client
     * paisa rokta hi nahi). Columns aur retention_releases table jaan bujh kar
     * chhodi hain taake kuch data na ure aur zaroorat pare to wapas on ho sake,
     * yahan sirf mojooda data ko 0 par le aate hain.
     *
     * Ye zaroori hai: agar purana retention_held bacha reh jata to client ko
     * "Received" kam dikhta aur cash profit ulta ho jata.
     */
    public function up(): void
    {
        // Jo rakam client ne rok rakhi thi wo ab poori mili hui mani jayegi.
        DB::table('client_payments')->update([
            'net_received_paisa'   => DB::raw('gross_amount_paisa'),
            'retention_held_paisa' => 0,
        ]);

        DB::table('projects')->update(['retention_percent' => 0]);

        // Soft delete, taake record mite nahi bas hisaab me na aaye.
        DB::table('retention_releases')->whereNull('deleted_at')->update(['deleted_at' => now()]);

        DB::table('settings')->where('key', 'default_retention')->delete();
    }

    public function down(): void
    {
        // Purana gross/net split wapas nahi banaya ja sakta, sirf releases
        // bahaal kar dete hain.
        DB::table('retention_releases')->update(['deleted_at' => null]);
    }
};
