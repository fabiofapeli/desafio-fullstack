<?php

namespace Src\Application\UseCases\DTO\Contract;

class ContractServiceInputDto
{
    public function __construct(
        public int $userId,
        public int $planId,
    ) {}
}
