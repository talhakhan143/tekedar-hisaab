<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Single admin user for now. The full demo project + financial data
     * seeder is added in Step 2.
     */
    public function run(): void
    {
        // Owner account — the only standard (non-developer) user.
        User::updateOrCreate(
            ['email' => 'm_ali@aliconsgroup.pk'],
            [
                'name'              => 'Owner',
                'password'          => Hash::make('m_ali_owner@786'),
                'is_developer'      => false,
                'email_verified_at' => now(),
            ],
        );

        // Hidden developer account. Not surfaced anywhere in the UI; can
        // change its own email/password without confirmation.
        User::updateOrCreate(
            ['email' => 'mr.talha143@gmail.com'],
            [
                'name'              => 'Developer',
                'password'          => Hash::make('Spazio@786'),
                'is_developer'      => true,
                'email_verified_at' => now(),
            ],
        );

        $this->call(DemoSeeder::class);
    }
}
