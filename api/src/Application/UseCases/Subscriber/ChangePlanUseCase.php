<?php

namespace Src\Application\UseCases\Subscriber;

use Src\Application\UseCases\DTO\Subscriber\ChangePlanInputDto;
use Src\Application\UseCases\DTO\Subscriber\ChangePlanOutputDto;
use Src\Domain\Exceptions\BusinessException;
use Src\Domain\Services\ContractService;

class ChangePlanUseCase
{
    public function __construct(private ContractService $contractService) {}

    /**
     * @throws BusinessException
     */
    public function execute(ChangePlanInputDto $input): ChangePlanOutputDto
    {
        $result = $this->contractService->changePlan(
            new ChangePlanInputDto(
                $input->userId,
                $input->newPlanId
            )
        );

        return new ChangePlanOutputDto(
            $result->contract,
            $result->payment
        );
    }
}
