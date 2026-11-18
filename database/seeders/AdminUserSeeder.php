<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Outside the local environment only the admin is created, with a random
     * password printed once; the demo users with known passwords are local only.
     */
    public function run(): void
    {
        $isLocal = app()->environment('local');
        $adminPassword = $isLocal ? 'password123' : Str::password(20);

        $admin = User::firstOrCreate(
            ['email' => 'admin@programmarti.com'],
            [
                'first_name' => 'Admin',
                'last_name' => 'User',
                'username' => 'admin',
                'password' => Hash::make($adminPassword),
            ]
        );

        $admin->assignRole('Admin');

        if (! $isLocal) {
            if ($admin->wasRecentlyCreated) {
                $this->command?->warn("Admin password (shown once, change it after login): {$adminPassword}");
            }

            return;
        }

        $developer = User::firstOrCreate(
            ['email' => 'developer@programmarti.com'],
            [
                'first_name' => 'Mario',
                'last_name' => 'Rossi',
                'username' => 'mario.rossi',
                'password' => Hash::make('password123'),
            ]
        );

        $developer->assignRole('Developer');

        $socialMedia = User::firstOrCreate(
            ['email' => 'social@programmarti.com'],
            [
                'first_name' => 'Giulia',
                'last_name' => 'Bianchi',
                'username' => 'giulia.bianchi',
                'password' => Hash::make('password123'),
            ]
        );

        $socialMedia->assignRole('Social Media Manager');
    }
}
