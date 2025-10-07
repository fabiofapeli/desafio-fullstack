<?php

namespace Src\Application\UseCases\Subscriber;

use Src\Application\UseCases\DTO\Subscriber\NewContractInputDto;
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
        $plan = PlanModel::findOrFail($input->planId);

        if (!$plan) {
            throw new BusinessException('Plano não encontrado.');
        }

        $contractOutput = $this->contractService->createNewContract(
            new NewContractInputDto($input->userId, $plan->id)
        );

        return new SubscriberPlanOutputDTO(
            $plan->toArray(),
            $contractOutput->payment
        );
    }
}
