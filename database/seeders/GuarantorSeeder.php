<?php

namespace Database\Seeders;

use App\Models\Guarantor;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class GuarantorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tenants = Tenant::all();

        if ($tenants->isEmpty()) {
            return;
        }

        $names = ['Carlos', 'Maria', 'Juan', 'Ana', 'Luis', 'Elena', 'Diego', 'Sofia', 'Pedro', 'Laura'];
        $surnames = ['Gomez', 'Rodriguez', 'Perez', 'Garcia', 'Martinez', 'Lopez', 'Sanchez', 'Gonzalez', 'Fernandez', 'Diaz'];

        for ($i = 0; $i < 10; $i++) {
            Guarantor::create([
                'first_name' => $names[$i],
                'last_name' => $surnames[$i],
                'dni' => 'G' . rand(10000000, 99999999),
                'address' => 'Calle Garante ' . ($i + 1),
                'whatsapp' => '+54911' . rand(10000000, 99999999),
                'email' => strtolower($names[$i]) . '@gmail.com',
                'tenant_id' => $tenants->random()->id,
            ]);
        }
    }
}
