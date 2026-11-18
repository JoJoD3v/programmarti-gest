<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            AdminUserSeeder::class,
        ]);

        // Demo data only for local development.
        if (! app()->environment('local')) {
            return;
        }

        $this->call([
            ClientSeeder::class,
            ProjectSeeder::class,
            ExpenseSeeder::class,
            WorkSeeder::class,
            PreventivoSeeder::class,
        ]);
    }
}
