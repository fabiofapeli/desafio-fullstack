<?php

namespace Src\Application\UseCases\DTO\Subscriber;

class GetActivePlanOutputDto
{
    public function __construct(
        public ?array $contract = null,
        public ?array $plan = null,
        public ?array $payments = null,
    ) {}
}

