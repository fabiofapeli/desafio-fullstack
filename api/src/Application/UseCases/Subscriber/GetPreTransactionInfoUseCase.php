<?php


namespace Src\Application\UseCases\Subscriber;

use Carbon\Carbon;
use Src\Application\UseCases\DTO\Subscriber\PreTransactionInputDto;
use Src\Application\UseCases\DTO\Subscriber\PreTransactionOutputDto;
use Src\Domain\Services\ContractService;
use Src\Infra\Eloquent\PlanModel;

class GetPreTransactionInfoUseCase
{
    public function __construct(private ContractService $contractService)
    {
    }

    public function execute(PreTransactionInputDto $input): PreTransactionOutputDto
    {
        $plan = PlanModel::findOrFail($input->planId);
        $now = Carbon::now();

        // 1) Sem contrato ativo válido => purchase
        if (!$this->contractService->hasValidActiveContract($input->userId, $now)) {
            return new PreTransactionOutputDto(
                plan: $plan->toArray(),
                action: 'purchase'
            );
        }

        // Existe contrato ativo e válido:
        $active = $this->contractService->getActivePlan($input->userId);

        // 2) Renovação (mesmo plano) => renew + janela
        if ((int)$active->plan_id === (int)$plan->id) {
            $window = $this->contractService->getRenewalWindow($active);

            return new PreTransactionOutputDto(
                plan: $plan->toArray(),
                action: 'renew',
                renewalWindow: $window,
                credit: null,
                price: null
            );
        }

        // 3) Troca de plano (plano diferente) => change_plan + (crédito, preço)
        $quote = $this->contractService->getChangePlanQuote($active, $plan, $now);

        return new PreTransactionOutputDto(
            plan: $plan->toArray(),
            action: 'change_plan',
            renewalWindow: null,
            credit: $quote['credit'],
            price: $quote['price']
        );
    }
}
