<?php

namespace Src\Application\UseCases\DTO\Subscriber;

class PreTransactionInputDto
{
    public function __construct(
        public int $userId,
        public int $planId
    ) {}
}
