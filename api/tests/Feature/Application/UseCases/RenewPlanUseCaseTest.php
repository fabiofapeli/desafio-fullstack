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
            'expiration_date' => Carbon::now()->addDays(3), // dentro da janela de 5 dias
            'next_renewal_available_at' => Carbon::now()->subDay(), // janela aberta
        ]);

        $useCase = new RenewPlanUseCase(new ContractService());
        $dto = new RenewPlanInputDto($user->id);

        $result = $useCase->execute($dto);

        $this->assertEquals('paid', $result->payment['status']);
    }

    public function test_cannot_renew_twice_in_the_same_window()
    {
        $user = UserModel::factory()->create();
        $plan = PlanModel::factory()->create(['price' => 100]);

        // contrato vence em 3 dias (já está na janela)
        ContractModel::factory()->create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'expiration_date' => now()->addDays(3),
            'next_renewal_available_at' => now()->subDay(), // janela aberta
        ]);

        $useCase = new RenewPlanUseCase(new ContractService());
        $dto = new RenewPlanInputDto($user->id);

        // 1ª renovação: OK
        $useCase->execute($dto);

        // 2ª tentativa: deve falhar porque a janela do novo ciclo ainda não abriu
        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('Renovação permitida apenas a partir de');
        $useCase->execute($dto);
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
