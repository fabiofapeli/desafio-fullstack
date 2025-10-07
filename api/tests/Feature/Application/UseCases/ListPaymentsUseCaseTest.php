<?php

namespace Tests\Feature\Application\UseCases;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Src\Application\UseCases\DTO\Payment\ListPaymentsInputDto;
use Src\Application\UseCases\Payment\ListPaymentsUseCase;
use Src\Domain\Services\PaymentService;
use Src\Infra\Eloquent\ContractModel;
use Src\Infra\Eloquent\PaymentModel;
use Src\Infra\Eloquent\PlanModel;
use Src\Infra\Eloquent\UserModel;
use Tests\TestCase;
use Carbon\Carbon;

class ListPaymentsUseCaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_list_payments_returns_formatted_history()
    {
        $user = UserModel::factory()->create();
        $plan = PlanModel::factory()->create(['description' => 'Plano Teste', 'price' => 120]);
        $contract = ContractModel::factory()->create(['user_id' => $user->id, 'plan_id' => $plan->id]);

        PaymentModel::factory()->create([
            'contract_id' => $contract->id,
            'action' => 'purchase',
            'type' => 'pix',
            'plan_value' => 120,
            'price' => 120,
            'credit' => 0,
            'payment_at' => Carbon::parse('2024-03-01'),
            'status' => 'paid',
        ]);

        $useCase = new ListPaymentsUseCase(new PaymentService());
        $output = $useCase->execute(new ListPaymentsInputDto($user->id));

        $this->assertCount(1, $output->payments);
        $this->assertEquals('Plano Teste', $output->payments[0]['plano']);
        $this->assertEquals('Compra', $output->payments[0]['tipo']);
        $this->assertEquals('PIX', $output->payments[0]['forma_pagamento']);
        $this->assertEquals('120,00', $output->payments[0]['valor_plano']);
    }
}
