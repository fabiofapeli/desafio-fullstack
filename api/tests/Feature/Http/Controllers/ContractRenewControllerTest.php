<?php

namespace Tests\Feature\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Src\Infra\Eloquent\ContractModel;
use Src\Infra\Eloquent\PlanModel;
use Src\Infra\Eloquent\UserModel;
use Tests\TestCase;

class ContractRenewControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_renew_contract_via_api()
    {
        $user = UserModel::factory()->create();
        $plan = PlanModel::factory()->create(['price' => 100]);

        ContractModel::factory()->create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'expiration_date' => Carbon::now()->addDays(5),
        ]);

        $response = $this->postJson('/api/contracts/renew');

        $response->assertStatus(200)
            ->assertJsonFragment(['status' => 'paid']);

        $this->assertDatabaseCount('payments', 1);
    }

    public function test_user_cannot_renew_if_no_active_contract()
    {
        PlanModel::factory()->create(['price' => 100]);

        $response = $this->postJson('/api/contracts/renew');

        $response->assertStatus(409)
            ->assertJsonFragment(['message' => 'Usuário não possui contrato ativo para renovação.']);
    }
}
