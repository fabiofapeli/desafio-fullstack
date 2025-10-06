<?php

namespace Src\Application\UseCases\DTO\Contract;

class NewContractOutputDto
{
    public function __construct(
        public array $contract,
        public array $payment,
    ) {}
}
