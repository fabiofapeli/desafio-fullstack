<?php
namespace Tests\Feature\Http\Controllers;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Src\Infra\Eloquent\PlanModel;
use Tests\TestCase;

class PlanControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_should_return_all_plans_in_resource_format()
    {
        // Arrange: cria 3 planos no banco
        PlanModel::factory()->create([
            'description' => 'Plano Bronze',
            'numberOfClients' => 10,
            'gigabytesStorage' => 5,
            'price' => 49.90,
            'active' => true,
        ]);

        PlanModel::factory()->create([
            'description' => 'Plano Prata',
            'numberOfClients' => 20,
            'gigabytesStorage' => 10,
            'price' => 99.90,
            'active' => true,
        ]);

        PlanModel::factory()->create([
            'description' => 'Plano Ouro',
            'numberOfClients' => 50,
            'gigabytesStorage' => 30,
            'price' => 199.90,
            'active' => false,
        ]);

        // Act: faz a requisição HTTP para o endpoint da API
        $response = $this->getJson('/api/plans');

        // Assert
        $response->assertStatus(200)
            ->assertJsonCount(3, 'data') // deve ter 3 registros
            ->assertJsonFragment(['description' => 'Plano Bronze'])
            ->assertJsonFragment(['description' => 'Plano Prata'])
            ->assertJsonFragment(['description' => 'Plano Ouro']);
    }
}
