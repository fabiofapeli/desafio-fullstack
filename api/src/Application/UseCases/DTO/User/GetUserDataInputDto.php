<?php

namespace Src\Application\UseCases\DTO\User;

class GetUserDataInputDto
{
    public function __construct(
        public int $userId
    ) {}
}
