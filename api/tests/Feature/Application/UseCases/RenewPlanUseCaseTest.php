<?php

namespace Tests\Feature\Application\UseCases;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Src\Application\UseCases\DTO\Subscriber\RenewPlanInputDto;
use Src\Application\UseCases\Subscriber\RenewPlanUseCase;
use Src\Domain\Services\ContractService;
use Src\Domain\Exceptions\BusinessException;
use Src\Infra\Eloquent\ContractModel;
use Src\Infra\Eloquent\PlanModel;
use Src\Infra\Eloquent\UserModel;
use Tests\TestCase;

class RenewPlanUseCaseTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @throws BusinessException
     */
    public function test_user_can_renew_active_plan()
    {
        $user = UserModel::factory()->create();
        $plan = PlanModel::factory()->create(['price' => 120]);

        ContractModel::factory()->create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'expiration_date' => Carbon::now()->addDays(10),
        ]);

        $useCase = new RenewPlanUseCase(new ContractService());
        $dto = new RenewPlanInputDto($user->id);

        $result = $useCase->execute($dto);

        $this->assertEquals('paid', $result->payment['status']);
    }

    public function test_should_throw_exception_if_no_active_contract()
    {
        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('Usuário não possui contrato ativo para renovação.');

        $user = UserModel::factory()->create();
        PlanModel::factory()->create(['price' => 100]);

        $useCase = new RenewPlanUseCase(new ContractService());
        $dto = new RenewPlanInputDto($user->id);

        $useCase->execute($dto);
    }
}
