<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Owner;
use App\Models\Property;
use Faker\Factory as Faker;

class DataSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('es_AR');
        $types = ['Casa', 'Dpto', 'Local'];

        // 8 Owners with 1 property
        for ($i = 0; $i < 8; $i++) {
            $owner = Owner::create([
                'first_name' => $faker->firstName,
                'last_name' => $faker->lastName,
                'dni' => $faker->unique()->randomNumber(8, true),
                'address' => $faker->address,
                'whatsapp' => $faker->phoneNumber,
                'email' => $faker->unique()->safeEmail,
            ]);

            $this->createProperty($owner, $types[rand(0, 2)], $faker);
        }

        // 1 Owner with 3 properties
        $owner3 = Owner::create([
            'first_name' => $faker->firstName,
            'last_name' => $faker->lastName,
            'dni' => $faker->unique()->randomNumber(8, true),
            'address' => $faker->address,
            'whatsapp' => $faker->phoneNumber,
            'email' => $faker->unique()->safeEmail,
        ]);
        for ($i = 0; $i < 3; $i++) {
            $this->createProperty($owner3, $types[$i % 3], $faker);
        }

        // 1 Owner with 4 properties
        $owner4 = Owner::create([
            'first_name' => $faker->firstName,
            'last_name' => $faker->lastName,
            'dni' => $faker->unique()->randomNumber(8, true),
            'address' => $faker->address,
            'whatsapp' => $faker->phoneNumber,
            'email' => $faker->unique()->safeEmail,
        ]);
        for ($i = 0; $i < 4; $i++) {
            $this->createProperty($owner4, $types[$i % 3], $faker);
        }

        // 1 Owner with 5 properties
        $owner5 = Owner::create([
            'first_name' => $faker->firstName,
            'last_name' => $faker->lastName,
            'dni' => $faker->unique()->randomNumber(8, true),
            'address' => $faker->address,
            'whatsapp' => $faker->phoneNumber,
            'email' => $faker->unique()->safeEmail,
        ]);
        for ($i = 0; $i < 5; $i++) {
            $this->createProperty($owner5, $types[$i % 3], $faker);
        }
    }

    private function createProperty($owner, $type, $faker)
    {
        Property::create([
            'type' => $type,
            'real_estate_id' => 'PART-' . $faker->unique()->randomNumber(6),
            'domain' => 'DOM-' . $faker->unique()->randomNumber(6),
            'street' => $faker->streetName,
            'number' => $faker->buildingNumber,
            'floor' => $type === 'Dpto' ? rand(1, 10) : null,
            'dept' => $type === 'Dpto' ? $faker->randomLetter : null,
            'location' => 'Rosario',
            'owner_id' => $owner->id,
        ]);
    }
}
