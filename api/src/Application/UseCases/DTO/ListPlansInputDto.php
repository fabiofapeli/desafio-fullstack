<?php

namespace Src\Application\UseCases\DTO;

class ListPlansInputDto
{
    public function __construct(
        public string $filter = ''
    ) {
    }
}
