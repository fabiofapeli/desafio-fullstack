<?php

namespace Src\Application\UseCases\DTO\Payment;

class ListPaymentsInputDto
{
    public function __construct(
        public int $userId
    ) {
    }
}
