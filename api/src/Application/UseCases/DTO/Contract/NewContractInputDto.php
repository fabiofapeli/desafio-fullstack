<?php
namespace Src\Application\UseCases\DTO\Contract;

class NewContractInputDto
{
    public function __construct(
        public int $userId,
        public int $planId,
    ) {}
}
