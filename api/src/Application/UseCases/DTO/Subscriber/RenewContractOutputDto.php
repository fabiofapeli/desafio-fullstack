<?php

namespace Src\Application\UseCases\DTO\Subscriber;

class RenewContractOutputDto
{
    public function __construct(
        public array $contract,
        public array $payment,
    ) {}
}
