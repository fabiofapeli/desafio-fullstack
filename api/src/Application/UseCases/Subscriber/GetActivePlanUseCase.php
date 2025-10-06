<?php

namespace Src\Application\UseCases\Subscriber;

use Src\Application\UseCases\DTO\Subscriber\GetActivePlanInputDto;
use Src\Application\UseCases\DTO\Subscriber\GetActivePlanOutputDto;
use Src\Domain\Services\ContractService;
use Src\Domain\Exceptions\BusinessException;

class GetActivePlanUseCase
{
    public function __construct(private readonly ContractService $contractService) {}

    /**
     * @throws BusinessException
     */
    public function execute(GetActivePlanInputDto $input): GetActivePlanOutputDto
    {
        $contract = $this->contractService->getActivePlan($input->userId);

        if (!$contract) {
            throw new BusinessException('Usuário não possui contrato ativo.');
        }

        $contract->load(['plan', 'payments']);

        return new GetActivePlanOutputDto(
            $contract->toArray(),
            $contract->plan?->toArray(),
            $contract->payments?->toArray()
        );
    }
}
