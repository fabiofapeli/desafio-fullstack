<?php

namespace Src\Application\UseCases\DTO\Plan;

class ListPlansInputDto
{
    public function __construct(
        public string $filter = ''
    ) {
    }
}
