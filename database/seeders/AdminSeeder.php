<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@leger.app'],
            [
                'name' => 'Administrator',
                'password' => Hash::make('admin123'),
                'role' => 'admin'
            ]
        );

        User::updateOrCreate(
            ['email' => 'gurubk@leger.app'],
            [
                'name' => 'Guru BK',
                'password' => Hash::make('gurubk123'),
                'role' => 'guru_bk'
            ]
        );
    }
}
