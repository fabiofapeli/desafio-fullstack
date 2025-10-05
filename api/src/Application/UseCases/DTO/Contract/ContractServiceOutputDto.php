<?php

namespace Src\Application\UseCases\DTO\Contract;

class ContractServiceOutputDto
{
    public function __construct(
        public array $contract,
        public array $payments,
    ) {}
}
