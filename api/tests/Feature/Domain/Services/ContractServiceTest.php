<?php

namespace Tests\Feature\Domain\Services;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Src\Application\UseCases\DTO\Contract\NewContractInputDto;
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
}

