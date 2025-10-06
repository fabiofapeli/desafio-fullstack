<?php

namespace Tests\Feature\Application\UseCases;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Src\Application\UseCases\Subscriber\GetActivePlanUseCase;
use Src\Application\UseCases\DTO\Subscriber\GetActivePlanInputDto;
use Src\Domain\Exceptions\BusinessException;
use Src\Domain\Services\ContractService;
use Src\Infra\Eloquent\ContractModel;
use Src\Infra\Eloquent\PlanModel;
use Src\Infra\Eloquent\UserModel;
use Tests\TestCase;

class GetActivePlanUseCaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_retrieve_active_contract()
    {
        $user = UserModel::factory()->create();
        $plan = PlanModel::factory()->create(['price' => 120]);

        $contract = ContractModel::factory()->create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'expiration_date' => Carbon::now()->addDays(10),
        ]);

        $service = new ContractService();
        $useCase = new GetActivePlanUseCase($service);

        $dto = new GetActivePlanInputDto($user->id);
        $output = $useCase->execute($dto);

        $this->assertEquals($contract->id, $output->contract['id']);
        $this->assertEquals('active', $output->contract['status']);
        $this->assertEquals($plan->id, $output->plan['id']);
    }

    public function test_should_throw_exception_if_no_active_contract()
    {
        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('Usuário não possui contrato ativo.');

        $user = UserModel::factory()->create();
        $service = new ContractService();
        $useCase = new GetActivePlanUseCase($service);

        $dto = new GetActivePlanInputDto($user->id);
        $useCase->execute($dto);
    }
}
