<?php

namespace Src\Application\UseCases\Subscriber;

use Src\Application\UseCases\DTO\Contract\ContractServiceInputDto;
use Src\Application\UseCases\DTO\Subscriber\SubscriberPlanInputDto;
use Src\Application\UseCases\DTO\Subscriber\SubscriberPlanOutputDTO;
use Src\Domain\Exceptions\BusinessException;
use Src\Domain\Services\ContractService;
use Src\Infra\Eloquent\PlanModel;

class SubscribePlanUseCase
{
    public function __construct(private ContractService $contractService) {}

    /**
     * @throws BusinessException
     */
    public function execute(SubscriberPlanInputDto $input): SubscriberPlanOutputDTO
    {
        $activeContract = $this->contractService->getActivePlan($input->userId);

        if ($activeContract) {
            throw new BusinessException('Usuário já possui um plano ativo.');
        }

        $plan = PlanModel::findOrFail($input->planId);

        $contractOutput = $this->contractService->createNewContract(
            new ContractServiceInputDto($input->userId, $plan->id)
        );

        return new SubscriberPlanOutputDTO(
            $plan->toArray(),
            $contractOutput->payments
        );
    }
}
