<?php

namespace Src\Application\UseCases;

use Src\Domain\Exceptions\BusinessException;
use Src\Domain\Services\ContractService;
use Src\Infra\Eloquent\PlanModel;

class SubscribePlanUseCase
{
    public function __construct(private ContractService $contractService) {}

    /**
     * @throws BusinessException
     */
    public function execute(int $userId, int $planId)
    {
        $activeContract = $this->contractService->getActivePlan($userId);

        if ($activeContract) {
            throw new BusinessException('Usuário já possui um plano ativo.');
        }

        $plan = PlanModel::findOrFail($planId);

        return $this->contractService->createNewContract($userId, $plan->id);
    }
}
