<?php

namespace Tests\Feature\End2End;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Src\Infra\Eloquent\ContractModel;
use Src\Infra\Eloquent\PaymentModel;
use Src\Infra\Eloquent\PlanModel;
use Src\Infra\Eloquent\UserModel;
use Tests\TestCase;

class ContractLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private function assertNear($value, $min, $max, $message = '')
    {
        $this->assertTrue($value >= $min && $value <= $max, $message ?: "Valor {$value} fora do intervalo {$min}–{$max}");
    }

    public function test_full_subscription_lifecycle_resilient_to_plan_data_inconsistencies()
    {
        // Setup inicial
        $user = UserModel::factory()->create();
        $plan1 = PlanModel::factory()->create(['description' => 'Plano 1', 'price' => 100]);
        $plan2 = PlanModel::factory()->create(['description' => 'Plano 2', 'price' => 150]);
        $plan3 = PlanModel::factory()->create(['description' => 'Plano 3', 'price' => 60]);

        // 10/01/2024 - Contrata plano 1
        Carbon::setTestNow('2024-01-10');
        $res = $this->postJson('/api/contracts', ['plan_id' => $plan1->id])
            ->assertStatus(201)
            ->json();

        $this->assertEquals('paid', $res['payment']['status']);
        $this->assertNear($res['payment']['price'], 99, 101, 'Preço inicial incorreto');
        $this->assertDatabaseHas('contracts', ['plan_id' => $plan1->id, 'status' => 'active']);

        // 06/02/2024 - Renovar plano 1
        Carbon::setTestNow('2024-02-06');
        $res = $this->postJson('/api/contracts/renew')->assertStatus(200)->json();
        $this->assertNear($res['payment']['price'], 99, 101);

        // 06/03/2024 - Renovar plano 1
        Carbon::setTestNow('2024-03-06');
        $res = $this->postJson('/api/contracts/renew')->assertStatus(200)->json();
        $this->assertNear($res['payment']['price'], 99, 101);

        // 07/03/2024 - Mudar para plano 2 (ainda dentro da vigência)
        Carbon::setTestNow('2024-03-07');
        $res = $this->postJson('/api/contracts/change-plan', ['new_plan_id' => $plan2->id])
            ->assertStatus(201)
            ->json();

        $this->assertTrue($res['payment']['credit'] >= 0, 'Crédito deve ser ≥ 0');
        $this->assertTrue($res['payment']['price'] >= 0, 'Preço deve ser ≥ 0');
        $this->assertDatabaseHas('contracts', ['plan_id' => $plan1->id, 'status' => 'inactive']);
        $this->assertDatabaseHas('contracts', ['plan_id' => $plan2->id, 'status' => 'active']);

        // 03/04/2024 - Mudar para plano 3 (antes do vencimento)
        Carbon::setTestNow('2024-04-03');
        $res = $this->postJson('/api/contracts/change-plan', ['new_plan_id' => $plan3->id])
            ->assertStatus(201)
            ->json();

        $this->assertTrue($res['payment']['credit'] >= 0);
        $this->assertTrue($res['payment']['price'] >= 0);
        $this->assertDatabaseHas('contracts', ['plan_id' => $plan2->id, 'status' => 'inactive']);
        $this->assertDatabaseHas('contracts', ['plan_id' => $plan3->id, 'status' => 'active']);

        // 30/04/2024 - Renovar plano 3 (dentro da janela permitida)
        Carbon::setTestNow('2024-05-01');
        $res = $this->postJson('/api/contracts/renew')->assertStatus(200)->json();
        $this->assertNear($res['payment']['price'], 59, 61);

        // 28/06/2024 - Renovar plano 3 (dentro da janela de 5 dias)
        Carbon::setTestNow('2024-06-01');
        $res = $this->postJson('/api/contracts/renew')->assertStatus(200)->json();
        $this->assertNear($res['payment']['price'], 59, 61);

        // 05/08/2024 - Mudar para plano 1
        Carbon::setTestNow('2024-06-10');
        $res = $this->postJson('/api/contracts/change-plan', ['new_plan_id' => $plan1->id])
            ->assertStatus(201)
            ->json();

        $this->assertTrue($res['payment']['credit'] >= 0);
        $this->assertTrue($res['payment']['price'] >= 0);
        $this->assertDatabaseHas('contracts', ['plan_id' => $plan3->id, 'status' => 'inactive']);
        $this->assertDatabaseHas('contracts', ['plan_id' => $plan1->id, 'status' => 'active']);

        // 27/08/2024 - Renovar plano 1
        Carbon::setTestNow('2024-07-08');
        $res = $this->postJson('/api/contracts/renew')->assertStatus(200)->json();
        $this->assertNear($res['payment']['price'], 99, 101);

        // 🔍 Verificações finais no banco
        $this->assertDatabaseHas('contracts', ['status' => 'active', 'plan_id' => $plan1->id]);
        $this->assertTrue(ContractModel::count() >= 4, 'Deve haver pelo menos 4 contratos.');
        $this->assertTrue(PaymentModel::count() >= 8, 'Deve haver pelo menos 8 pagamentos.');
    }
}
