<?php

namespace Tests\Feature\Domain\Services;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Src\Domain\Services\ContractService;
use Src\Infra\Eloquent\ContractModel;
use Src\Infra\Eloquent\PlanModel;
use Src\Infra\Eloquent\UserModel;
use Tests\TestCase;

class ContractServiceTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function test_get_active_plan_returns_contract_when_active()
    {
        $user = UserModel::factory()->create();

        $active = ContractModel::factory()->create([
            'user_id'         => $user->id,
            'status'          => 'active',
            'expiration_date' => Carbon::now()->addDays(5),
        ]);

        $service = new ContractService();
        $found = $service->getActivePlan($user->id);

        $this->assertNotNull($found);
        $this->assertEquals($active->id, $found->id);
    }

    /** @test */
    public function test_get_active_plan_returns_null_when_no_active_contract()
    {
        $user = UserModel::factory()->create();

        ContractModel::factory()->create([
            'user_id'         => $user->id,
            'status'          => 'inactive',
            'expiration_date' => Carbon::now()->subDay(),
        ]);

        $service = new ContractService();
        $result = $service->getActivePlan($user->id);

        $this->assertNull($result);
    }

    /** @test */
    public function test_get_renewal_window_uses_5_days_before_expiration_when_next_not_set()
    {
        $user = UserModel::factory()->create();
        $plan = PlanModel::factory()->create();

        $expiration = Carbon::create(2024, 3, 10); // 10/03/2024
        $contract = ContractModel::factory()->create([
            'user_id'         => $user->id,
            'plan_id'         => $plan->id,
            'status'          => 'active',
            'started_at'      => $expiration->copy()->subMonth(),
            'expiration_date' => $expiration,
            'next_renewal_available_at' => null,
        ]);

        $service = new ContractService();
        $window  = $service->getRenewalWindow($contract);

        $this->assertEquals('2024-03-05', $window['available_from']);   // 5 dias antes
        $this->assertEquals('2024-03-10', $window['expiration_date']);
    }

    /** @test */
    public function test_check_renewal_allowed_blocks_before_window()
    {
        $user = UserModel::factory()->create();
        $plan = PlanModel::factory()->create();

        $expiration     = Carbon::create(2024, 3, 10); // 10/03
        $availableFrom  = $expiration->copy()->subDays(5); // 05/03
        $contract = ContractModel::factory()->create([
            'user_id'         => $user->id,
            'plan_id'         => $plan->id,
            'status'          => 'active',
            'started_at'      => $expiration->copy()->subMonth(),
            'expiration_date' => $expiration,
            'next_renewal_available_at' => $availableFrom,
        ]);

        $service = new ContractService();

        // 01/03 ainda antes da janela
        $now = Carbon::create(2024, 3, 1);
        $check = $service->checkRenewalAllowed($contract, $now);

        $this->assertFalse($check['allowed']);
        $this->assertStringContainsString('apenas a partir de', strtolower($check['reason'] ?? ''));
    }

    /** @test */
    public function test_quote_change_plan_calculates_credit_and_price_correctly()
    {
        // Fixamos a "data atual" para tornar o cálculo determinístico
        $now        = Carbon::create(2024, 3, 1);
        $expiration = Carbon::create(2024, 3, 11); // faltam 10 dias

        $oldPlanPrice = 90.00;   // preço plano antigo
        $newPlanPrice = 150.00;  // preço novo plano

        $service = new ContractService();
        $quote   = $service->quoteChangePlan($oldPlanPrice, $newPlanPrice, $expiration, $now);

        // Reproduz a mesma fórmula do service para validar o número esperado
        $cycleStart  = $expiration->copy()->subMonthNoOverflow();   // 2024-02-11
        $daysInCycle = $cycleStart->daysInMonth;                    // fev/2024 = 29
        $dailyOld    = $daysInCycle > 0 ? $oldPlanPrice / $daysInCycle : 0.0;
        $expectedCredit = round($dailyOld * 10, 2);                 // 10 dias restantes
        $expectedPrice  = round(max(0, $newPlanPrice - $expectedCredit), 2);

        $this->assertEquals($expectedCredit, $quote['credit']);
        $this->assertEquals($expectedPrice, $quote['price']);
        $this->assertEquals(10, $quote['days_remaining']);
        $this->assertEqualsWithDelta($dailyOld, $quote['daily_old'], 0.0001);
    }
}
