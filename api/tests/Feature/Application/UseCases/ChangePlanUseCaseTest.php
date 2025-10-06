<?php

namespace Tests\Feature\Application\UseCases;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Src\Application\UseCases\DTO\Subscriber\ChangePlanInputDto;
use Src\Application\UseCases\Subscriber\ChangePlanUseCase;
use Src\Domain\Exceptions\BusinessException;
use Src\Domain\Services\ContractService;
use Src\Infra\Eloquent\ContractModel;
use Src\Infra\Eloquent\PlanModel;
use Src\Infra\Eloquent\UserModel;
use Tests\TestCase;

class ChangePlanUseCaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_change_plan_and_old_contract_becomes_inactive()
    {
        $user = UserModel::factory()->create();

        $oldPlan = PlanModel::factory()->create(['price' => 100]);
        $newPlan = PlanModel::factory()->create(['price' => 150]);

        $contract = ContractModel::factory()->create([
            'user_id' => $user->id,
            'plan_id' => $oldPlan->id,
            'status' => 'active',
            'expiration_date' => Carbon::now()->addDays(10),
        ]);

        $useCase = new ChangePlanUseCase(new ContractService());
        $input = new ChangePlanInputDto($user->id, $newPlan->id);
        $result = $useCase->execute($input);

        $contract->refresh();
        $this->assertEquals('inactive', $contract->status);

        $this->assertEquals($newPlan->id, $result->contract['plan_id']);
        $this->assertEquals('active', $result->contract['status']);
        $this->assertEquals('paid', $result->payment['status']);
    }

    public function test_should_throw_exception_if_no_active_contract()
    {
        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('Usuário não possui contrato ativo para mudança de plano.');

        $user = UserModel::factory()->create();
        $newPlan = PlanModel::factory()->create(['price' => 150]);

        $useCase = new ChangePlanUseCase(new ContractService());
        $input = new ChangePlanInputDto($user->id, $newPlan->id);
        $useCase->execute($input);
    }
}
