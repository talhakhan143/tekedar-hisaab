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
        User::updateOrCreate(
            ['email' => 'admin@tekedar.test'],
            [
                'name'     => 'Tekedar Admin',
                'password' => Hash::make('password'),
            ],
        );

        $this->call(DemoSeeder::class);
    }
}
