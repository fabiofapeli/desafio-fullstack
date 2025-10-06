<?php
namespace Src\Application\UseCases\DTO\Subscriber;

class GetActivePlanInputDto
{
    public function __construct(
        public int $userId
    ) {}
}
