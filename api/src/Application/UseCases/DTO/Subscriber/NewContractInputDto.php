<?php
namespace Src\Application\UseCases\DTO\Subscriber;

class NewContractInputDto
{
    public function __construct(
        public int $userId,
        public int $planId,
    ) {}
}
