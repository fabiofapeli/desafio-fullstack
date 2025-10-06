<?php

namespace Src\Application\UseCases\DTO\Contract;

class RenewContractInputDto
{
    public function __construct(
        public int $userId
    ) {}
}
