<?php

namespace Src\Application\UseCases\DTO\Plan;

class ListPlansOutputDto
{
    public function __construct(
        public array $items
    ) {
    }
}
