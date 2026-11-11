<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class LaggerInversionesSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'lagger@inmoapp.com'],
            [
                'name' => 'lagger-inversiones',
                'password' => \Hash::make('lagger123'),
            ]
        );
    }
}
