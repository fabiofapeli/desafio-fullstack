<?php

namespace Src\Application\UseCases\DTO;

class ListPlansOutputDto
{
    public function __construct(
        public array $items
    ) {
    }
}
