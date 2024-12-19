<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (!User::where('email', 'superadmin@example.com')->exists()) {
            User::create([
                'name' => 'Superadmin',
                'email' => 'superadmin@coboy.com',
                'password' => Hash::make('12345678'),
                'role' => 'superadmin',
            ]);
        }
    }
}
