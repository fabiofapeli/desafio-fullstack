<?php

namespace Src\Domain\Entities;

class Plan
{
    public function __construct(
        public ?int $id,
        public string $description,
        public int $numberOfClients,
        public int $gigabytesStorage,
        public float $price,
        public bool $active = true
    ) {}
}
