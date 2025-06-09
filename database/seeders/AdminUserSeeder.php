<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@programmarti.com'],
            [
                'first_name' => 'Admin',
                'last_name' => 'User',
                'username' => 'admin',
                'password' => Hash::make('password123'),
            ]
        );

        $admin->assignRole('Admin');

        // Create a developer user
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

        // Create a social media manager
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
