<?php

namespace Src\Application\UseCases\DTO\Subscriber;

class RenewContractInputDto
{
    public function __construct(
        public int $userId
    ) {}
}
