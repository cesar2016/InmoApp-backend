<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ContractControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_stores_contract_with_new_property_tenant_and_guarantor(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $payload = [
            'start_date' => '2026-06-01',
            'end_date' => '2027-05-31',
            'rent_amount' => 194730,
            'increase_frequency_months' => 6,
            'tenant_data' => [
                'first_name' => 'MATIAS EDUARDO', 'last_name' => 'IGLESIAS', 'dni' => '30111222',
                'email' => 'matias.iglesias@test.com', 'whatsapp' => '3498123456', 'address' => 'Pringles 720',
            ],
            'property_data' => [
                'street' => 'Cochabamba', 'number' => '1', 'floor' => '', 'dept' => '',
                'location' => 'San Cristóbal', 'type' => 'Departamento',
            ],
            'owner_data' => [
                'first_name' => 'RODRIGO', 'last_name' => 'BALBI', 'dni' => '30222333',
                'email' => 'rodrigo.balbi@test.com', 'whatsapp' => '', 'address' => 'Pringles 720',
            ],
            'guarantors' => [
                ['first_name' => 'JORGE', 'last_name' => 'PEREZ', 'dni' => '30111777', 'address' => 'Rosas s/n'],
            ],
        ];

        $response = $this->postJson('/api/contracts', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('property.type', 'Departamento')
            ->assertJsonPath('tenant.last_name', 'IGLESIAS');

        $this->assertDatabaseHas('properties', ['street' => 'Cochabamba', 'type' => 'Departamento']);
        $this->assertDatabaseHas('guarantors', ['first_name' => 'JORGE', 'dni' => '30111777']);
    }

    public function test_allows_the_same_guarantor_on_multiple_contracts(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $base = [
            'start_date' => '2026-06-01',
            'end_date' => '2027-05-31',
            'rent_amount' => 194730,
            'increase_frequency_months' => 6,
            'property_data' => [
                'street' => 'Caseros', 'number' => '100', 'location' => 'San Cristóbal', 'type' => 'Casa',
            ],
            'owner_data' => [
                'first_name' => 'RODRIGO', 'last_name' => 'BALBI', 'dni' => '30222333',
                'email' => 'rodrigo.balbi@test.com', 'whatsapp' => '', 'address' => 'Pringles 720',
            ],
            'guarantors' => [
                ['first_name' => 'JORGE', 'last_name' => 'PEREZ', 'dni' => '30111777', 'address' => 'Rosas s/n'],
            ],
        ];

        $first = $this->postJson('/api/contracts', $base + [
            'tenant_data' => [
                'first_name' => 'MARIA', 'last_name' => 'LOPEZ', 'dni' => '30111888',
                'email' => 'maria.lopez@test.com', 'whatsapp' => '', 'address' => 'Av 1',
            ],
        ]);
        $first->assertStatus(201);

        $second = $this->postJson('/api/contracts', $base + [
            'tenant_data' => [
                'first_name' => 'CARLOS', 'last_name' => 'GOMEZ', 'dni' => '30111999',
                'email' => 'carlos.gomez@test.com', 'whatsapp' => '', 'address' => 'Av 2',
            ],
        ]);
        $second->assertStatus(201);
    }
}
