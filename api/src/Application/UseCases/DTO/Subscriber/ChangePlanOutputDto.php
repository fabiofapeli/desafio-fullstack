<?php

namespace Src\Application\UseCases\DTO\Subscriber;

class ChangePlanOutputDto
{
    public function __construct(
        public array $contract,
        public array $payment,
    ) {}
}
