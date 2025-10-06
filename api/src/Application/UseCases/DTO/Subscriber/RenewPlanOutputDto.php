<?php

namespace Src\Application\UseCases\DTO\Subscriber;

class RenewPlanOutputDto
{
    public function __construct(
        public array $contract,
        public array $payment
    ) {}
}
