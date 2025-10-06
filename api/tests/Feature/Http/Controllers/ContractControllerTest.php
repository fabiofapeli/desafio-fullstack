<?php

namespace Tests\Feature\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Src\Infra\Eloquent\ContractModel;
use Src\Infra\Eloquent\PlanModel;
use Src\Infra\Eloquent\UserModel;
use Tests\TestCase;

class ContractControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_new_contract_via_api()
    {
        $user = UserModel::factory()->create();
        $plan = PlanModel::factory()->create(['price' => 120]);

        $response = $this->postJson('/api/contracts', [
            'plan_id' => $plan->id,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('plan.id', $plan->id)
            ->assertJsonPath('payment.status', 'paid')
            ->assertJsonPath('payment.price', 120);

        $this->assertDatabaseHas('contracts', [
            'plan_id' => $plan->id,
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('payments', [
            'status' => 'paid',
        ]);
    }

    public function test_user_cannot_subscribe_if_already_has_active_contract()
    {
        $user = UserModel::factory()->create();
        $plan = PlanModel::factory()->create(['price' => 100]);

        ContractModel::factory()->create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'expiration_date' => Carbon::now()->addDays(5),
        ]);

        $response = $this->postJson('/api/contracts', [
            'plan_id' => $plan->id,
        ]);

        $response->assertStatus(409) // HTTP Conflict (não mais 500)
        ->assertJsonFragment(['message' => 'Usuário já possui um plano ativo.']);
    }

    public function test_api_returns_active_contract()
    {
        $user = UserModel::factory()->create();
        $plan = PlanModel::factory()->create(['price' => 120]);

        ContractModel::factory()->create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'expiration_date' => now()->addDays(5),
        ]);

        $response = $this->getJson('/api/contracts/active');

        $response->assertStatus(200)
            ->assertJsonFragment(['status' => 'active'])
            ->assertJsonFragment(['price' => 120]);
    }

    public function test_api_returns_404_if_no_active_contract()
    {
        $response = $this->getJson('/api/contracts/active');

        $response->assertStatus(404)
            ->assertJsonFragment(['message' => 'Usuário não possui contrato ativo.']);
    }

}
