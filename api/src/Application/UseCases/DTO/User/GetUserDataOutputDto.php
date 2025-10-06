<?php

namespace Src\Application\UseCases\DTO\User;

class GetUserDataOutputDto
{
    public function __construct(
        public array $user
    ) {}
}

