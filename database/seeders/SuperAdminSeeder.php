<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@inmoapp.com'],
            [
                'name' => 'SuperAdmin',
                'password' => \Hash::make('admin123'),
            ]
        );
    }
}
