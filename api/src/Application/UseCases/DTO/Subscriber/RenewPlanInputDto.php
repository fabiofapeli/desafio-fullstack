<?php
namespace Src\Application\UseCases\DTO\Subscriber;

class RenewPlanInputDto
{
    public function __construct(
        public int $userId
    ) {}
}
