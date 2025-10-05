<?php

namespace Tests\Feature\Application\UseCases;

use Carbon\Carbon;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Src\Application\UseCases\SubscribePlanUseCase;
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

        $service = new ContractService();
        $useCase = new SubscribePlanUseCase($service);

        $contract = $useCase->execute($user->id, $plan->id);

        $this->assertEquals($user->id, $contract->user_id);
        $this->assertEquals('active', $contract->status);
        $this->assertEquals('paid', $contract->payments->first()->status);
    }

    public function test_user_cannot_subscribe_if_active_contract_exists()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Usuário já possui um plano ativo.');

        $user = UserModel::factory()->create();
        $plan = PlanModel::factory()->create(['price' => 100]);

        ContractModel::factory()->create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'expiration_date' => Carbon::now()->addDays(10),
        ]);

        $service = new ContractService();
        $useCase = new SubscribePlanUseCase($service);

        $useCase->execute($user->id, $plan->id);
    }

    public function test_should_throw_exception_if_plan_not_exists()
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $user = UserModel::factory()->create();
        $service = new ContractService();
        $useCase = new SubscribePlanUseCase($service);

        $useCase->execute($user->id, 9999); // plano inexistente
    }
}
