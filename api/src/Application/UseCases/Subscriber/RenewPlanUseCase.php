<?php

namespace Src\Application\UseCases\Subscriber;

use Src\Application\UseCases\DTO\Contract\RenewContractInputDto;
use Src\Application\UseCases\DTO\Subscriber\RenewPlanInputDto;
use Src\Application\UseCases\DTO\Subscriber\RenewPlanOutputDto;
use Src\Domain\Exceptions\BusinessException;
use Src\Domain\Services\ContractService;

class RenewPlanUseCase
{
    public function __construct(private ContractService $contractService) {}

    /**
     * @throws BusinessException
     */
    public function execute(RenewPlanInputDto $input): RenewPlanOutputDto
    {
        // Executa a renovação no service
        $output = $this->contractService->renewContract(
            new RenewContractInputDto($input->userId)
        );

        return new RenewPlanOutputDto(
            contract: $output->contract,
            payment: $output->payment
        );
    }
}
