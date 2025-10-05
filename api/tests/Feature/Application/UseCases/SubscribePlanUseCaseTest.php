<?php

namespace Tests\Feature\Application\UseCases;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Src\Application\UseCases\DTO\Subscriber\SubscriberPlanInputDto;
use Src\Application\UseCases\Subscriber\SubscribePlanUseCase;
use Src\Domain\Exceptions\BusinessException;
use Src\Domain\Services\ContractService;
use Src\Infra\Eloquent\ContractModel;
use Src\Infra\Eloquent\PlanModel;
use Src\Infra\Eloquent\UserModel;
use Tests\TestCase;

class SubscribePlanUseCaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_subscribe_plan_when_no_active_contract()
    {
        $user = UserModel::factory()->create();
        $plan = PlanModel::factory()->create(['price' => 100]);

        $useCase = new SubscribePlanUseCase(new ContractService());
        $result = $useCase->execute(new SubscriberPlanInputDto($user->id, $plan->id));

        $this->assertEquals($plan->id, $result->plan['id']);
        $this->assertEquals('paid', $result->payment[0]['status']);
    }

    public function test_user_cannot_subscribe_if_active_contract_exists()
    {
        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('Usuário já possui um plano ativo.');

        $user = UserModel::factory()->create();
        $plan = PlanModel::factory()->create();

        ContractModel::factory()->create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'expiration_date' => Carbon::now()->addDays(5),
        ]);

        $useCase = new SubscribePlanUseCase(new ContractService());
        $useCase->execute(new SubscriberPlanInputDto($user->id, $plan->id));
    }

    public function test_should_throw_exception_if_plan_not_exists()
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $user = UserModel::factory()->create();
        $useCase = new SubscribePlanUseCase(new ContractService());
        $useCase->execute(new SubscriberPlanInputDto($user->id, 9999));
    }
}
