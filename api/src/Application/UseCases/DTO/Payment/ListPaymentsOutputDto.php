<?php

namespace Src\Application\UseCases\DTO\Payment;

class ListPaymentsOutputDto
{
    public function __construct(
        /** @var array<int, array<string, mixed>> */
        public array $payments
    ) {
    }
}
