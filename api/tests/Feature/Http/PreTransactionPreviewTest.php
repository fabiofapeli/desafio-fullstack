<?php

namespace Tests\Feature\Http;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Src\Infra\Eloquent\ContractModel;
use Src\Infra\Eloquent\PlanModel;
use Src\Infra\Eloquent\UserModel;
use Tests\TestCase;

class PreTransactionPreviewTest extends TestCase
{
    use RefreshDatabase;

    private function assertMoneyEquals($expected, $actual, $precision = 2, $label = 'valor')
    {
        $this->assertEquals(
            round($expected, $precision),
            round((float)$actual, $precision),
            "Esperado {$label}: {$expected}, obtido: {$actual}"
        );
    }

    /** @test */
    public function it_returns_purchase_when_user_has_no_active_contract()
    {
        // Arrange: primeiro usuário terá id=1 (controller usa userId=1)
        $user = UserModel::factory()->create();
        $plan = PlanModel::factory()->create(['price' => 100]);

        Carbon::setTestNow(Carbon::create(2024, 1, 10, 12));

        // Act
        $res = $this->getJson('/api/contracts/preview?plan_id=' . $plan->id)
            ->assertStatus(200)
            ->json();

        // Assert
        $this->assertEquals('purchase', $res['action']);
        $this->assertEquals($plan->id, $res['plan']['id']);
        $this->assertNull($res['renewal_window']);
        $this->assertNull($res['credit']);
        $this->assertNull($res['price']);
    }

    /** @test */
    public function it_returns_renew_with_window_when_same_plan_is_active()
    {
        // Arrange
        $user = UserModel::factory()->create(); // id = 1
        $plan = PlanModel::factory()->create(['price' => 120]);

        // Contrato ativo para o mesmo plano
        ContractModel::factory()->create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'started_at' => Carbon::create(2024, 2, 10, 12),
            'expiration_date' => Carbon::create(2024, 3, 10, 12),          // vence em 10/03
            'next_renewal_available_at' => Carbon::create(2024, 3, 5, 12), // janela abre 05/03
        ]);

        Carbon::setTestNow(Carbon::create(2024, 3, 6, 12)); // dentro do ciclo

        // Act
        $res = $this->getJson('/api/contracts/preview?plan_id=' . $plan->id)
            ->assertStatus(200)
            ->json();

        // Assert
        $this->assertEquals('renew', $res['action']);
        $this->assertEquals($plan->id, $res['plan']['id']);
        $this->assertNotEmpty($res['renewal_window']);
        $this->assertEquals('2024-03-05', $res['renewal_window']['available_from']);
        $this->assertEquals('2024-03-10', $res['renewal_window']['expiration_date']);
        $this->assertNull($res['credit']);
        $this->assertNull($res['price']);
    }

    /** @test */
    public function it_returns_change_plan_with_credit_and_price_when_different_plan_is_selected()
    {
        // Arrange
        $user = UserModel::factory()->create(); // id = 1
        $oldPlan = PlanModel::factory()->create(['price' => 100]);
        $newPlan = PlanModel::factory()->create(['price' => 150]);

        // Agora: 01/03/2024 12:00 (mês com 31 dias)
        Carbon::setTestNow(Carbon::create(2024, 3, 1, 12));

        // Contrato ativo do plano antigo, vencendo em 31/03 (30 dias restantes)
        ContractModel::factory()->create([
            'user_id' => $user->id,
            'plan_id' => $oldPlan->id,
            'status' => 'active',
            'started_at' => Carbon::create(2024, 2, 1, 12),
            'expiration_date' => Carbon::create(2024, 3, 31, 12),
        ]);

        // Cálculo esperado (espelha a regra do ContractService::getChangePlanQuote):
        // daysRemaining = 30; daysInMonth = 31; oldDaily = 100/31; credit = 96.77; price = 53.23
        $expectedCredit = round((100 / 31) * 30, 2); // 96.77
        $expectedPrice  = round(max(0, 150 - $expectedCredit), 2); // 53.23

        // Act
        $res = $this->getJson('/api/contracts/preview?plan_id=' . $newPlan->id)
            ->assertStatus(200)
            ->json();

        // Assert
        $this->assertEquals('change_plan', $res['action']);
        $this->assertEquals($newPlan->id, $res['plan']['id']);
        $this->assertNull($res['renewal_window']);

        $this->assertMoneyEquals($expectedCredit, $res['credit'], 2, 'crédito');
        $this->assertMoneyEquals($expectedPrice, $res['price'], 2, 'preço');
    }
}
