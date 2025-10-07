<?php

namespace Src\Application\UseCases\DTO\Subscriber;

class NewContractOutputDto
{
    public function __construct(
        public array $contract,
        public array $payment,
    ) {}
}
