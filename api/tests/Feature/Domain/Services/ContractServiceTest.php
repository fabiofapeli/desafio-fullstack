<?php

namespace Tests\Feature\Domain\Services;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Src\Application\UseCases\DTO\Contract\NewContractInputDto;
use Src\Application\UseCases\DTO\Subscriber\ChangePlanInputDto;
use Src\Domain\Exceptions\BusinessException;
use Src\Domain\Services\ContractService;
use Src\Infra\Eloquent\ContractModel;
use Src\Infra\Eloquent\PlanModel;
use Src\Infra\Eloquent\UserModel;
use Tests\TestCase;

class ContractServiceTest extends TestCase
{
    use RefreshDatabase;
    public function test_get_active_plan_returns_contract_when_active()
    {
        $user = UserModel::factory()->create();

        $active = ContractModel::factory()->create([
            'user_id' => $user->id,
            'status' => 'active',
            'expiration_date' => Carbon::now()->addDays(5),
        ]);

        $service = new ContractService();

        $found = $service->getActivePlan($user->id);

        $this->assertNotNull($found);
        $this->assertEquals($active->id, $found->id);
    }

    public function test_get_active_plan_returns_null_when_no_active_contract()
    {
        $user = UserModel::factory()->create();

        // contrato expirado
        ContractModel::factory()->create([
            'user_id' => $user->id,
            'status' => 'inactive',
            'expiration_date' => Carbon::now()->subDay(),
        ]);

        $service = new ContractService();
        $result = $service->getActivePlan($user->id);

        $this->assertNull($result);
    }

    /**
     * @throws BusinessException
     */
    public function test_create_new_contract_creates_payment_and_sets_active()
    {
        $user = UserModel::factory()->create();
        $plan = PlanModel::factory()->create(['price' => 99.9]);

        $service = new ContractService();

        $input = new NewContractInputDto(
            $user->id,
            $plan->id
        );

        $result = $service->createNewContract($input);

        $this->assertEquals('active', $result->contract['status']);
        $this->assertEquals('paid', $result->payment['status']);
        $this->assertEquals($plan->price, $result->payment['price']);
    }

    public function test_change_plan_throws_exception_if_no_active_contract()
    {
        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('Usuário não possui contrato ativo para mudança de plano.');

        $user = UserModel::factory()->create();
        $newPlan = PlanModel::factory()->create(['price' => 200]);

        $service = new ContractService();
        $service->changePlan(
            new ChangePlanInputDto($user->id, $newPlan->id)
        );
    }

    public function test_change_plan_adds_extra_days_based_on_credit()
    {
        $user = UserModel::factory()->create();

        $oldPlan = PlanModel::factory()->create(['price' => 90]);   // R$ 3/dia
        $newPlan = PlanModel::factory()->create(['price' => 150]);  // R$ 5/dia

        // 10 dias restantes no plano antigo = 10 * 3 = R$30 de crédito
        // No plano novo (5/dia) => 6 dias de crédito
        $oldContract = ContractModel::factory()->create([
            'user_id' => $user->id,
            'plan_id' => $oldPlan->id,
            'status' => 'active',
            'expiration_date' => Carbon::now()->addDays(10),
        ]);

        $service = new ContractService();
        $result = $service->changePlan(
            new ChangePlanInputDto($user->id, $newPlan->id)
        );

        $expirationDate = Carbon::parse($result->contract['expiration_date']);
        $expectedDate = Carbon::now()->addMonth()->addDays(6);

        $this->assertTrue($expirationDate->isSameDay($expectedDate));
    }
}

