<?php

namespace Tests\Feature\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Src\Infra\Eloquent\ContractModel;
use Src\Infra\Eloquent\PlanModel;
use Src\Infra\Eloquent\UserModel;
use Tests\TestCase;

class ChangePlanControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_change_plan_via_api()
    {
        $user = UserModel::factory()->create();

        $oldPlan = PlanModel::factory()->create(['price' => 100]);
        $newPlan = PlanModel::factory()->create(['price' => 150]);

        ContractModel::factory()->create([
            'user_id' => $user->id,
            'plan_id' => $oldPlan->id,
            'status' => 'active',
            'expiration_date' => Carbon::now()->addDays(20),
        ]);

        $response = $this->postJson('/api/contracts/change-plan', [
            'new_plan_id' => $newPlan->id,
        ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['status' => 'active'])
            ->assertJsonFragment(['price' => $newPlan->price]);
    }

    public function test_user_cannot_change_plan_if_no_active_contract()
    {
        $newPlan = PlanModel::factory()->create(['price' => 150]);

        $response = $this->postJson('/api/contracts/change-plan', [
            'new_plan_id' => $newPlan->id,
        ]);

        $response->assertStatus(409)
            ->assertJsonFragment(['message' => 'Usuário não possui contrato ativo para mudança de plano.']);
    }
}
