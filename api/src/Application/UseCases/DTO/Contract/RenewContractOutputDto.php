<?php

namespace Src\Application\UseCases\DTO\Contract;

class RenewContractOutputDto
{
    public function __construct(
        public array $contract,
        public array $payment,
    ) {}
}
